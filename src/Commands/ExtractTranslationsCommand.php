<?php

namespace Snadnee\Toolkit\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Snadnee\Toolkit\Commands\Parsers\FrontendTranslationsParser;
use Snadnee\Toolkit\Commands\Parsers\TranslationsParser;

class ExtractTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'toolkit:extract-translations
    { --L|lang=cs : Specify name of the output file (--lang=cs by default) }
    { --frontend-path=resources/nuxt/admin/ : Specify frontend path (--frontend-path=resources/nuxt/admin/) }
    { --N|nova : Extract translations from Nova }
    { --F|fillament : Extract translations from Fillament }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extracts translatable strings from source code into JSON file.';

    private TranslationsParser $translationsParser;

    private FrontendTranslationsParser $frontendTranslationsParser;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(TranslationsParser $translationsParser, FrontendTranslationsParser $frontendTranslationsParser)
    {
        parent::__construct();
        $this->translationsParser = $translationsParser;
        $this->frontendTranslationsParser = $frontendTranslationsParser;
    }

    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws FileNotFoundException
     */
    public function handle()
    {
        if ($this->option('nova')) {
            $this->translationsParser
                ->setOutputLanguage($this->option('lang'))
                ->setOutput($this->output)
                ->parseNovaTranslations();

            $this->info('Translations generating successfully.');

            return 0;
        }

        $this->frontendTranslationsParser
            ->setOutput($this->output)
            ->setOutputLanguage($this->option('lang'))
            ->setFrontEndPath($this->option('frontend-path'))
            ->parseFrontendTranslations();

        return 0;
    }
}
