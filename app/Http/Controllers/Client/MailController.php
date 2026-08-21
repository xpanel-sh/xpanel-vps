<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\EmailAccount;
use App\Services\DaemonClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MailController extends Controller
{
    public function __construct(private DaemonClient $daemon) {}

    // ── Webmail UI ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenant = $request->attributes->get('tenant');

        if (! $tenant) {
            return redirect()->route('client.login')->withErrors(['email' => 'No tenant assigned.']);
        }

        $accounts = EmailAccount::query()
            ->with('domain')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->latest()
            ->get();

        $primaryAccount = $accounts->first();

        return view('client.mail.xmail', compact('tenant', 'accounts', 'primaryAccount'));
    }

    // ── IMAP proxy endpoints (JSON) ────────────────────────────────────────────

    public function apiFolders(Request $request)
    {
        $account = $this->resolveAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Account not found or forbidden'], 403);
        }

        try {
            $data = $this->daemon->mailFolders($account->email);

            return response()->json($data);
        } catch (\Throwable $e) {
            Log::warning('XMail folders failed', ['account' => $account->email, 'error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    public function apiMessages(Request $request)
    {
        $account = $this->resolveAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $folder = $request->input('folder', 'INBOX');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));

        try {
            $data = $this->daemon->mailMessages($account->email, $folder, $page, $perPage);

            return response()->json($data);
        } catch (\Throwable $e) {
            Log::warning('XMail messages failed', ['account' => $account->email, 'folder' => $folder, 'error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    public function apiMessage(Request $request)
    {
        $account = $this->resolveAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $folder = $request->input('folder', 'INBOX');
        $uid = (int) $request->input('uid');
        if ($uid < 1) {
            return response()->json(['error' => 'uid is required'], 400);
        }

        try {
            $data = $this->daemon->mailMessage($account->email, $folder, $uid);

            return response()->json($data);
        } catch (\Throwable $e) {
            Log::warning('XMail message failed', ['account' => $account->email, 'uid' => $uid, 'error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    public function apiFlag(Request $request)
    {
        $account = $this->resolveAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'folder' => 'required|string',
            'uid' => 'required|integer|min:1',
            'flag' => 'required|in:seen,flagged,deleted',
            'set' => 'required|boolean',
        ]);

        try {
            $result = $this->daemon->mailFlag(
                $account->email,
                $validated['folder'],
                $validated['uid'],
                $validated['flag'],
                (bool) $validated['set']
            );

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    public function apiMove(Request $request)
    {
        $account = $this->resolveAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'folder' => 'required|string',
            'uid' => 'required|integer|min:1',
            'target_folder' => 'required|string',
        ]);

        try {
            $result = $this->daemon->mailMove(
                $account->email,
                $validated['folder'],
                $validated['uid'],
                $validated['target_folder']
            );

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    public function apiDelete(Request $request)
    {
        $account = $this->resolveAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'folder' => 'required|string',
            'uid' => 'required|integer|min:1',
        ]);

        try {
            $result = $this->daemon->mailDelete($account->email, $validated['folder'], $validated['uid']);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    // ── SMTP proxy endpoint ────────────────────────────────────────────────────

    public function apiSend(Request $request)
    {
        $account = $this->resolveAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'to' => 'required|array|min:1',
            'to.*' => 'required|email',
            'cc' => 'nullable|array',
            'cc.*' => 'email',
            'bcc' => 'nullable|array',
            'bcc.*' => 'email',
            'subject' => 'required|string|max:998',
            'text' => 'nullable|string',
            'html' => 'nullable|string',
            'in_reply_to' => 'nullable|string',
            'references' => 'nullable|string',
        ]);

        $payload = array_merge($validated, [
            'from' => $account->email,
            'cc' => $validated['cc'] ?? [],
            'bcc' => $validated['bcc'] ?? [],
        ]);

        try {
            $result = $this->daemon->mailSend($payload);

            return response()->json($result);
        } catch (\Throwable $e) {
            Log::warning('XMail send failed', ['from' => $account->email, 'error' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    // ── Folder management ──────────────────────────────────────────────────────

    public function apiFolderCreate(Request $request)
    {
        $account = $this->resolveAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $name = trim($request->input('name', ''));
        if ($name === '') {
            return response()->json(['error' => 'name is required'], 400);
        }

        try {
            $result = $this->daemon->mailFolderCreate($account->email, $name);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    public function apiFolderDelete(Request $request)
    {
        $account = $this->resolveAccount($request);
        if (! $account) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $name = trim($request->input('name', ''));
        if ($name === '') {
            return response()->json(['error' => 'name is required'], 400);
        }

        try {
            $result = $this->daemon->mailFolderDelete($account->email, $name);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    /**
     * Resolves the active email account for the request.
     * The caller can pass ?account=email or we pick the tenant's first active account.
     */
    private function resolveAccount(Request $request): ?EmailAccount
    {
        $tenant = $request->attributes->get('tenant');
        $emailId = $request->input('account_id');
        $email = $request->input('account');

        $query = EmailAccount::where('tenant_id', $tenant->id)->where('status', 'active');

        if ($emailId) {
            return $query->find($emailId);
        }
        if ($email) {
            return $query->where('email', $email)->first();
        }

        return $query->latest()->first();
    }
}
