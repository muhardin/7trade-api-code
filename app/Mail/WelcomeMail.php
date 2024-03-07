<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;
    protected $content;
    protected $subjects;

    public function __construct($content, $subjects)
    {
        $this->content = $content;
        $this->subjects = $subjects;
    }

    public function build()
    {
        return $this->subject($this->subjects)->view('emails.welcomeMail')->with([
            'messages' => $this->content,
        ]);
    }
}
