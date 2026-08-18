<?php

namespace App\Http\Controllers\Crm;

use App\Models\EmailTemplate;
use App\Models\ProposalTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        $emailTemplates = EmailTemplate::forUser($user)
            ->orderBy('name')
            ->get();

        $proposalTemplates = ProposalTemplate::forUser($user)
            ->orderBy('name')
            ->get();

        return Inertia::render('templates/index', [
            'emailTemplates' => $emailTemplates,
            'proposalTemplates' => $proposalTemplates,
        ]);
    }

    public function editEmail(EmailTemplate $template): Response
    {
        return Inertia::render('templates/email-edit', [
            'template' => $template,
        ]);
    }

    public function updateEmail(Request $request, EmailTemplate $template)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'status' => ['sometimes', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $template->update($data);

        return redirect()->route('templates.index')
            ->with('success', 'Email template updated.');
    }

    public function editProposal(ProposalTemplate $template): Response
    {
        return Inertia::render('templates/proposal-edit', [
            'template' => $template,
        ]);
    }

    public function updateProposal(Request $request, ProposalTemplate $template)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sections' => ['sometimes', 'array'],
            'terms' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $template->update($data);

        return redirect()->route('templates.index')
            ->with('success', 'Proposal template updated.');
    }
}
