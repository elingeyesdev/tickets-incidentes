<?php

declare(strict_types=1);

namespace App\Features\ContentManagement\Events;

use App\Features\ContentManagement\Models\HelpCenterArticle;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArticlePublished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public HelpCenterArticle $article
    ) {
    }
}
