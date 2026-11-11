<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?User $user = null,
        public ?string $adminId = null,
        public ?string $loginUrl = null,
        public ?string $userName = null,
        public ?string $userEmail = null
    ) {
        $this->userName = $this->userName ?: ($this->user?->name ?? 'AgriCo Member');
        $this->userEmail = $this->userEmail ?: ($this->user?->email ?? '');
        $this->loginUrl = $loginUrl ?: 'https://agrico-web-production.up.railway.app/login';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[AgriCo] Account Registration Approved - Welcome to AgriCo!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-approved',
            with: [
                'user'      => $this->user,
                'userName'  => $this->userName,
                'userEmail' => $this->userEmail,
                'adminId'   => $this->adminId,
                'loginUrl'  => $this->loginUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
