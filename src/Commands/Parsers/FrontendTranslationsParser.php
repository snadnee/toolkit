<?php

namespace Snadnee\Toolkit\Commands\Parsers;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;

class FrontendTranslationsParser extends Command
{
    private string $outputLanguage;
    private string $frontendPath;
    private array $translations = [];

    public function parseFrontendTranslations(): void
    {
        $this->setUp();
        $files = [];
        // get all files in given relative path recursively
        $this->getAllFiles('', $files);
        $this->parseFiles($files);
        $this->saveTranslations();
    }

    private function setUp(): void
    {
        $frontendPath = base_path($this->frontendPath);
        $langPath = lang_path();

//         Configure frontend disk
        config(['filesystems.disks.frontend' => [
            'driver' => 'local',
            'root' => $frontendPath,
            'links' => 'skip'
        ]]);

        $this->info("Setting up frontend disk at: $frontendPath", OutputInterface::VERBOSITY_VERBOSE);

        // Configure lang disk
        config(['filesystems.disks.lang' => [
            'driver' => 'local',
            'root' => $langPath,
        ]]);

        $this->info("Setting up lang disk at: $langPath", OutputInterface::VERBOSITY_VERBOSE);
    }

    private function parseFiles($files) {
        $allMatches = [];
        foreach ($files as $file) {
            $this->info("Parsing file '$file'.", OutputInterface::VERBOSITY_VERY_VERBOSE);
            $file = str_replace(Storage::disk('frontend')->path(''), '' ,$file);
            $matches = [];
            /**
             * Matches substring of calling of translate function '$_()' with all possible quote marks.
             * E.g.: $_("...", "..."), $_('...', '...'), $_("...", '...'), $_('...', "..."), $_('...', `...`),
             * $_("...", `...`) OR $_(["'`]...["'`], ["'`]...["'`], ...)
             */
            preg_match_all(
                '/(\$_\(\s*[\"\'`]\S+[\"\'`],\s*[\"\'`][^\"\'`]+[\"\'`]\s*\))|(\$_\(\s*[\"\'`]\S+[\"\'`],\s*[\"\'`][^\"\'`]+[\"\'`]\s*,\s*\S+\s*\))|(\$_\(\s*[\"\'`]\S+[\"\'`],\s*[\"\'`][^\"\'`]+[\"\'`]\s*,\s*\S+\s*,\s*\S+\s*,\s*\{[^}]+\}\s*\))/U',
                Storage::disk('frontend')->get($file),
                $matches,
            );
            foreach ($matches as $match) {
                foreach ($match as $translation) {
                    if ($translation) {
                        $translation = trim($translation);
                        // replace anything up to first occurrence of a quote-mark (including)
                        $translation = preg_replace('/^[\S\s]*["\'`]/U', '', $translation);
                        // replace last quote-mark
                        $translation = preg_replace('/([\S\s]*)["\'`]\s*\)/', "$1", $translation);
                        $translation = explode(',', $translation, 2);
                        if (count($translation) < 2) {
                            $this->error('unsupported translation:');
                            $this->error("Soubor: $file");
                            print_r($translation);
                            return 1;
                        }
                        $translationValue = [];
                        preg_match('/["\'`][^"\'`]+["\'`]/', $translation[1], $translationValue);
                        if (count($translationValue) === 1) {
                            $translation[1] = $translationValue[0];
                            $translation[1] = substr($translation[1], 0, -1);
                        }

                        // remove last character in a string (quote-mark)
                        $translation[0] = substr($translation[0], 0, -1);
                        // replace whitespace up to first occurrence of a quote-mark (including)
                        $translation[1] = preg_replace('/^\s*[\'"`]/U', '', $translation[1]);

                        if (array_key_exists($translation[0], $allMatches) && $allMatches[$translation[0]] !== $translation[1]) {

                            $this->error('Duplicitní klíč překladu s rozdílnou hodnotou:');
                            $this->error("Soubor: $file");

                            $this->line($translation[0] . ' = ' . $translation[1]);
                            $this->line($translation[0] . ' = ' . $allMatches[$translation[0]]);

                            return 1;
                        } else {
                            // 'mb_convert_encoding()' prevents 'Malformed UTF-8 characters, possibly incorrectly encoded' error
                            $allMatches[$translation[0]] = mb_convert_encoding($translation[1], 'UTF-8', 'UTF-8');
                        }
                    }
                }
            }
        }

        $this->translations = $allMatches;

        return $this;
    }

    private function saveTranslations(): static {
        if (count($this->translations)) {
            $this->info('Total translations: ' . count($this->translations));
            $outputFileName = Str::of($this->outputLanguage)->finish('.json');
            $json = json_encode($this->translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (json_last_error() === 0) {
                Storage::disk('lang')->put(
                    $outputFileName,
                    $json
                );
            } else {
                $this->error("Array to JSON conversion failed: " . json_last_error_msg());
            }
        }

        return $this;
    }

    private function getAllFiles($path, &$outputFiles)
    {
        if (is_dir(Storage::disk('frontend')->path($path))) {

            $dirsAndFiles = array_merge(Storage::disk('frontend')->directories($path), Storage::disk('frontend')->files($path));

            foreach ($dirsAndFiles as $dirOrFile) {
                if (is_dir(Storage::disk('frontend')->path($dirOrFile)) && !$this->isExcludedDir($dirOrFile)) {
                    $this->getAllFiles($dirOrFile, $outputFiles);
                } else {
                    if (!$this->isExcludedFile($dirOrFile)) {
                        $outputFiles[] = $dirOrFile;
                    }
                }
            }
        }
    }

    private function isExcludedDir($dir): bool
    {
        $isExcluded = in_array($dir, ['node_modules', 'dist', 'assets', 'static', '.nuxt', '.output']);
        if ($isExcluded) $this->line("Directory '$dir' is excluded.", null, OutputInterface::VERBOSITY_VERBOSE);

        return $isExcluded;
    }

    private function isExcludedFile($file): bool
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $isExcluded = !in_array($extension, ['vue', 'js', 'ts']);

        if ($isExcluded) $this->line("File '$file' is excluded.", null, OutputInterface::VERBOSITY_VERY_VERBOSE);

        return $isExcluded;
    }

    public function setOutputLanguage(string $outputLanguage): static
    {
        $this->outputLanguage = $outputLanguage;

        return $this;
    }

    public function setFrontEndPath(string $path): static
    {
        $this->frontendPath = $path;

        return $this;
    }

    public function setOutput(OutputStyle $output): static
    {
        $this->output = $output;

        return $this;
    }
}
