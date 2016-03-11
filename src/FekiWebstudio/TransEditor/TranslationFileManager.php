<?php
/**
 * Created by Feki Webstudio - 2016. 03. 10. 8:34
 * @author Zsolt
 * @copyright Copyright (c) 2016, Feki Webstudio Kft.
 */

namespace FekiWebstudio\TransEditor;

use File;
use InvalidArgumentException;

/**
 * Class TranslationFileManager is responsible for reading and
 * writing Laravel translation files.
 *
 * @package FekiWebstudio\TransEditor
 */
class TranslationFileManager
{
    /**
     * Reads a translation group and returns the values grouped to
     * an array with keys as entry and translation array as value.
     *
     * @param string $translationGroup
     * @return array
     */
    public function read($translationGroup)
    {
        // Get all locales and the fallback directory
        $locales = collect($this->getAvailableLocales());
        $fallbackLocale = $this->getFallbackLocale();

        // Get the fallback file
        $fallbackFile = $this->getTranslationFilePath($translationGroup, $fallbackLocale);

        // Check if fallback file exists
        if (! File::exists($fallbackFile)) {
            throw new InvalidArgumentException("Specified translation group does not exist.");
        }

        // Get group entries from the fallback language
        $fallback = require($fallbackFile);

        // Get entries
        $localeEntries = [$fallbackLocale => $fallback];

        foreach ($locales->except($fallbackLocale) as $locale) {
            if (File::exists($this->getTranslationFilePath($translationGroup, $locale))) {
                $localeEntries[$locale] = require($this->getTranslationFilePath($translationGroup, $locale));
            } else {
                $localeEntries[$locale] = $fallback;
            }
        }

        // Fill array with translation entries
        $entries = [];

        // Loop through entries - only use entries that exist in the fallback file
        foreach (array_keys($fallback) as $entryKey) {
            $entries[$entryKey] = [];

            // Add all translations
            foreach ($locales as $locale) {
                // Check if entry exists
                if (array_key_exists($entryKey, $localeEntries[$locale])) {
                    // Entry exists
                    $entries[$entryKey][$locale] = $localeEntries[$locale][$entryKey];
                } else {
                    // Entry doesn't exist, use fallback
                    $entries[$entryKey][$locale] = $fallback[$entryKey];
                }
            }
        }

        // Return entries
        return $entries;
    }

    /**
     * Gets the array of all available locales.
     *
     * @return array
     */
    public function getAvailableLocales()
    {
        return $this->getLocales($this->getLocalesDirectory());
    }

    /**
     * Gets the array of the available languages.
     *
     * @param string $path
     * @return array
     */
    private function getLocales($path)
    {
        // Get subdirectories
        return collect(File::directories($path))->map(function ($item, $key) {
            return pathinfo($item, PATHINFO_BASENAME);
        });
    }

    /**
     * Gets the path to the language files' directory.
     *
     * @return string
     */
    private function getLocalesDirectory()
    {
        // Get path from config
        return config('transeditor.language_file_path');
    }

    /**
     * Gets the applications fallback locale.
     *
     * @return string
     */
    private function getFallbackLocale()
    {
        return config('app.fallback_locale');
    }

    /**
     * Gets the path to a translation group file.
     *
     * @param string $translationGroup
     * @param string $locale
     * @return string
     */
    private function getTranslationFilePath($translationGroup, $locale)
    {
        return $this->getLocalesDirectory() .
        DIRECTORY_SEPARATOR .
        $locale .
        DIRECTORY_SEPARATOR .
        $translationGroup .
        '.php';
    }

    /**
     * Writes the files of the specified translation group.
     *
     * @param string $translationGroup
     * @param array $translations
     */
    public function write($translationGroup, $translations)
    {
        // Get all locales
        $locales = $this->getLocales($this->getLocalesDirectory());

        // Loop through translations and write corresponding files
        foreach ($locales as $locale) {
            if (array_key_exists($locale, $translations)) {
                // Get filename
                $translationFile = $this->getTranslationFilePath($translationGroup, $locale);

                // Write file
                $this->writeArrayToFile($translationFile, $translations[$locale]);
            }
        }
    }

    /**
     * Writes an array to a file.
     *
     * @param string $fileName
     * @param array $array
     */
    private function writeArrayToFile($fileName, $array)
    {
        // Delete file if exists
        if (File::exists($fileName)) {
            File::delete($fileName);
        }

        // Write php header
        File::append($fileName, "<?php\n\n");

        // Write array
        File::append($fileName, "return " . $this->arrayToString($array));

        // Write file end
        File::append($fileName, ";");
    }

    /**
     * Returns the PHP string notation of the array.
     *
     * @param array|string $array
     * @return string
     */
    private function arrayToString($array)
    {
        return var_export($array, true);
    }

    /**
     * Get the array of all available translation groups.
     *
     * @return array
     */
    public function getAvailableTranslationGroups()
    {
        // Use the fallback locale as the default
        $defaultLocale = $this->getFallbackLocale();

        // Get files in the default directory
        $files = File::files($this->getLocalesDirectory() . DIRECTORY_SEPARATOR . $defaultLocale);
        $files = collect($files);

        // Return array of filenames without extension
        return $files
            ->map(function ($item, $key) {
                return pathinfo($item, PATHINFO_FILENAME);
            })
            ->toArray();
    }
}