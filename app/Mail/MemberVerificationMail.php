<?php

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Member $member, public array $query = []) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifikasi Email Member',
        );
    }

    public function content(): Content
    {
        $verifyUrl = route('members.verify', ['token' => $this->member->verification_token]);
        if (! empty($this->query)) {
            $verifyUrl .= '?'.http_build_query($this->query);
        }

        return new Content(
            view: 'livewire.self-order.emails.member-verification',
            with: [
                'member' => $this->member,
                'verifyUrl' => $verifyUrl,
            ],
        );
    }
}
