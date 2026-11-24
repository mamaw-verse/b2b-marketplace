<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Seo\BuildRobotsTxt;
use Illuminate\Http\Response;

final class RobotsController extends Controller
{
    public function __invoke(BuildRobotsTxt $buildRobotsTxt): Response
    {
        return response($buildRobotsTxt->handle(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
