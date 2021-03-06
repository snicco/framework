<?php

declare(strict_types=1);

namespace Tests\integration\Mail\fixtures\Emails;

use Snicco\Mail\Email;

final class EmbeddedByPathAttachmentMail extends Email
{
    
    public function configure()
    {
        $this->subject('foo')->text('öö')
             ->html('<h1>ÜÜ</h1>')
             ->embedFromPath(
                 dirname(__DIR__, 2).'/fixtures/php-elephant.jpg',
                 'my-elephant',
                 'image/jpeg'
             );
    }
    
}