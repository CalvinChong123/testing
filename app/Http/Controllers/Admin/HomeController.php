<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function spa(Request $request, $any = null)
    {
        $app_name = config('app.name');
        $title = ucwords($app_name);
        $image = '/assets/images/og.png';
        $description = "Welcome to $title.";

        $data = [
            'title' => $title,
            'image' => $image,
            'description' => $description,
            'mix_app_path' => 'applications/vue-demo-admin-spa', // vue-demo-admin-spa or vue-admin-spa
            'robots' => 'noindex',
        ];

        return view('welcome-vue-admin', $data);
    }

    public function getRobotsTxtFile()
    {
        $robots = ['User-agent: *', 'Disallow: /'];
        $robotText = implode(PHP_EOL, $robots);

        return response($robotText, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
