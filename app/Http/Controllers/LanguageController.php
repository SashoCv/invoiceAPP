<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    protected array $availableLocales = ['mk', 'en'];

    public function switch(string $locale): RedirectResponse
    {
        if (in_array($locale, $this->availableLocales)) {
            session(['locale' => $locale]);
            App::setLocale($locale);
        }

        return redirect()->back();
    }
}
