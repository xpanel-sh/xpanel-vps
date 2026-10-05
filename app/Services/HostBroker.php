<?php

namespace App\Services;

use App\Models\HostBrokerOperation;
use App\Models\HostInstance;
use RuntimeException;
use Throwable;

class HostBroker
{
    public function __construct(private HostBrokerActionPolicy $policy, private ServerCommandRunner $commands) {}

    public function execute(array $payload, string $signature): string
    {
        $payload = $this->validatedPayload($payload);
        $instance = HostInstance::with('tenant')->where('uuid', $payload['instance_id'])->firstOrFail();
        $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        if (! hash_equals(hash_hmac('sha256', $canonical, (string) $instance->broker_secret), strtolower($signature))) {
            throw new RuntimeException('Firma del broker inválida.');
        }
        if (abs(time() - $payload['timestamp']) > 60) {
            throw new RuntimeException('La solicitud del broker expiró.');
        }

        $operation = HostBrokerOperation::create([
            'host_instance_id' => $instance->id,
            'request_id' => $payload['request_id'],
            'action' => $payload['action'],
            'arguments' => $payload['arguments'],
            'status' => 'received',
        ]);

        try {
            $this->policy->authorize($instance, $payload['action'], $payload['arguments']);
            $operation->update(['status' => 'authorized']);
            if ($payload['action'] === 'panel-domain-set') {
                $domain = $payload['arguments'][0];
                app(HostInstancePanelDomainManager::class)->stage($instance, $domain);
                if (! config('xpanel.native_hosting.apply_system_changes')) {
                    $operation->update(['status' => 'staged', 'output' => 'url=https://'.$domain]);

                    return 'url=https://'.$domain;
                }

                app(HostInstancePanelDomainManager::class)->apply($instance->fresh());
                $operation->update(['status' => 'completed', 'output' => 'url=https://'.$domain]);

                return 'url=https://'.$domain;
            }
            if (! config('xpanel.native_hosting.apply_system_changes')) {
                $operation->update(['status' => 'staged', 'output' => 'authorized-staged']);

                return 'authorized-staged';
            }
            $input = $payload['input'] === null ? null : base64_decode($payload['input'], true);
            if ($input === false || strlen((string) $input) > 131072) {
                throw new RuntimeException('La entrada de la operación es inválida.');
            }
            $output = $this->commands->run(array_merge([
                'sudo', '-n', config('xpanel.host_instances.broker_helper'), 'execute',
                $instance->uuid, $instance->system_user, $instance->release_path, $instance->instance_root,
                $payload['action'],
            ], $payload['arguments']), $input, 600);
            $this->policy->complete($instance, $payload['action'], $payload['arguments']);
            $operation->update(['status' => 'completed', 'output' => mb_substr($output, 0, 65535)]);

            return $output;
        } catch (Throwable $exception) {
            $operation->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 65535)]);
            throw $exception;
        }
    }

    private function validatedPayload(array $payload): array
    {
        $expected = ['instance_id', 'request_id', 'timestamp', 'action', 'arguments', 'input'];
        if (array_keys($payload) !== $expected || ! is_string($payload['instance_id']) || ! preg_match('/^[a-f0-9-]{36}$/', $payload['instance_id'])
            || ! is_string($payload['request_id']) || ! preg_match('/^[a-f0-9]{32}$/', $payload['request_id'])
            || ! is_int($payload['timestamp']) || ! is_string($payload['action']) || ! is_array($payload['arguments'])
            || array_filter($payload['arguments'], fn ($argument) => ! is_string($argument)) !== []
            || array_filter($payload['arguments'], fn ($argument) => strlen($argument) > 4096) !== []
            || strlen($payload['action']) > 64
            || (! is_null($payload['input']) && (! is_string($payload['input']) || strlen($payload['input']) > 175000))) {
            throw new RuntimeException('Formato de solicitud del broker inválido.');
        }

        return $payload;
    }
}
