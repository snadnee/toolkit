<?php

namespace Snadnee\Toolkit\Commands\Parsers;

use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Symfony\Component\Console\Output\OutputInterface;

class TranslationsParser extends Command
{
    public const DISKS_TO_CHECK = [
        'nova' => 'app/Nova',
        'livewire' => 'app/Livewire',
        'filament' => 'app/Filament',
        'views' => 'resources/views',
    ];

    public array $availableDisks = [];

    //    private string $outputFileName;
    private string $outputLanguage;

    private string $defaultOutputLanguage = 'en';

    public function parseAllTranslations(): void
    {
        $this->setUp();

        foreach ($this->availableDisks as $disk) {
            $files = Storage::disk($disk)->allFiles();

            $this->parseFiles('/__\((.*?)\)/', $files, $disk);
        }

        $this->cleanUp();
    }

    private function setUp(): void
    {
        foreach (self::DISKS_TO_CHECK as $disk => $path) {
            $this->configureDisk($disk, $path);
        }

        $langPath = lang_path();

        // Configure lang disk
        config(['filesystems.disks.lang' => [
            'driver' => 'local',
            'root' => $langPath,
        ]]);

        $this->info("Setting up lang disk at: $langPath", OutputInterface::VERBOSITY_VERBOSE);
    }

    private function configureDisk(string $disk, string $path): void
    {
        $diskPath = base_path($path);

        if (File::exists($diskPath)) {
            config(["filesystems.disks.$disk" => [
                'driver' => 'local',
                'root' => $diskPath,
            ]]);

            $this->info("Setting up $disk disk at: $diskPath", OutputInterface::VERBOSITY_VERBOSE);
            $this->availableDisks[] = $disk;
        }
    }

    private function cleanUp(): void
    {
        foreach ($this->availableDisks as $disk) {
            config(["filesystems.disks.$disk" => null]);
        }

        // Remove lang disk
        config(['filesystems.disks.lang' => null]);
    }

    private function parseFiles(string $regex, array $files, string $disk): void
    {
        $matches = collect();

        $files = collect($files)
            ->reverse();

        foreach ($files as $filePath) {
            $fileContent = Storage::disk($disk)->get($filePath);

            preg_match_all($regex, $fileContent, $rawMatches);

            $cleanMatches = $this->cleanMatches($rawMatches);

            $matches = $matches->merge($cleanMatches);
        }

        $this->saveTranslations($matches, 'en');
        $this->saveTranslations($matches, $this->outputLanguage);
    }

    private function cleanMatches(array $rawMatches): Collection
    {
        $cleanMatches = collect();

        // Pick just full matches
        if (is_array($rawMatches[0])) {
            $rawMatches = $rawMatches[0];
        }

        foreach ($rawMatches as $rawMatch) {
            $match = Str::of($rawMatch);

            // Choose proper quotes
            $quote = "'";
            if ($match->contains('__("')) {
                $quote = '"';
            }

            // Clean up match
            $match = $match->substrReplace('', strpos($match, "$quote)"), Str::length($match))
                ->replace("__($quote", '');

            $cleanMatches->push((string) $match);
        }

        return $cleanMatches;
    }

    private function saveTranslations(Collection $matches, string $outputLanguage): void
    {
        // Prepare output file name
        $outputFileName = Str::of($outputLanguage)->finish('.json');

        $this->ensureFileExists($outputFileName);

        // Get translation file content
        $outputFile = Storage::disk('lang')->get($outputFileName);

        $oldContent = $this->getOldContent($outputFile);
        $content = $this->getNewContent($matches, $outputFile, '"', $outputLanguage);

        // If there is no new content
        if ((string) $content->trim('}') === PHP_EOL) {
            $oldContent = $oldContent->rtrim(PHP_EOL)->rtrim(',');
        }

        // Save translations
        Storage::disk('lang')->put(
            $outputFileName,
            $oldContent."\t".$content
        );
    }

    public function setOutputLanguage(string $outputLanguage): static
    {
        $this->outputLanguage = $outputLanguage;

        return $this;
    }

    public function setOutput(OutputStyle $output): static
    {
        $this->output = $output;

        return $this;
    }

    private function ensureFileExists($outputFileName): void
    {
        // Make sure translation file already exists
        if (! Storage::disk('lang')->exists($outputFileName)) {
            Storage::disk('lang')->put($outputFileName, '{'.PHP_EOL.'}');
        }
    }

    private function getNewContent(Collection $matches, string $outputFile, string $quote, string $defaultLanguage): Stringable
    {
        $content = $matches
            // Filter out those that already exists
            ->filter(function ($match) use ($outputFile, $defaultLanguage) {
                $defaultLanguage = Str::upper($defaultLanguage);

                $exists = Str::contains($outputFile, '"'.$match.'"');

                if ($exists) {
                    $this->warn("[$defaultLanguage] Translation $match already exists. Skipping!", OutputInterface::VERBOSITY_DEBUG);
                } else {
                    $this->info("[$defaultLanguage] Translation $match created successfully!", OutputInterface::VERBOSITY_DEBUG);
                }

                if (Str::contains($match, '$')) {
                    $this->warn("[$defaultLanguage] Variable involved in translation: '$match'. Manual review needed!", OutputInterface::VERBOSITY_VERBOSE);

                    return false;
                }

                return ! $exists;
            })
            // Add translation under the translation key
            ->map(function ($match) use ($quote, $defaultLanguage) {
                $lastWord = Str::of(Arr::last(explode('.', $match)));

                if ($defaultLanguage === $this->defaultOutputLanguage) {
                    $lastWord = $lastWord->studly()->snake(' ')->ucfirst();
                }

                return "{$quote}$match{$quote}: {$quote}$lastWord{$quote},";
            })
            // Turn it to string
            ->implode(PHP_EOL."\t");

        // Clean up string to json format
        return Str::of($content)
            ->whenEndsWith(',', fn ($string) => $string->replaceLast(',', null))
            ->append(PHP_EOL.'}');
    }

    private function getOldContent($outputFile): Stringable
    {
        // Get array with lines without empty lines
        $array = Arr::where(explode(PHP_EOL, $outputFile), fn ($line) => ! empty($line));

        // Remove "}"
        array_pop($array);

        // Make it string again
        $content = Str::of(implode(PHP_EOL, $array));

        // Finish up with comma to easily connect new content
        $content = $content->finish(',')->finish(PHP_EOL);

        // Remove comma when it's creating new translation file
        return $content->whenEndsWith('{,'.PHP_EOL, fn ($string) => $string->replaceLast(',', null));
    }
}
