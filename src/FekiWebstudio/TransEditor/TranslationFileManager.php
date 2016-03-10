<?php
/**
 * Created by Feki Webstudio - 2016. 03. 10. 8:34
 * @author Zsolt
 * @copyright Copyright (c) 2016, Feki Webstudio Kft.
 */

namespace FekiWebstudio\TransEditor;

use File;

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
     * an array with keys as locales.
     *
     * @param string $translationGroup
     * @return array
     */
    public function read($translationGroup)
    {
        // Get all locales and the fallback directory
        $locales = collect($this->getLocales($this->getLocalesDirectory()));
        $fallbackLocale = $this->getFallbackLocale();

        // Get the fallback file
        $fallbackFile = $this->getTranslationGroupFilePath($translationGroup, $fallbackLocale);

        // Check if fallback file exists
        if (! File::exists($fallbackFile)) {
            throw new \InvalidArgumentException("Specified translation group does not exist.");
        }

        // Get group entries from the fallback language
        $fallback = require($fallbackFile);

        // Use fallback as the default
        $entries = [];
        $entries[$fallbackLocale] = $fallback;

        // Get other languages
        foreach ($locales->except($fallbackLocale) as $locale) {
            if (File::exists($this->getTranslationGroupFilePath($translationGroup, $locale))) {
                // File exists, get the entries of it
                $entries[$locale] = $this->getEntriesOrDefault($translationGroup, $locale, $fallback);
            } else {
                // File does not exist, use fallback
                $entries[$locale] = $fallback;
            }
        }

        // Return entries
        return $entries;
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
                $translationFile = $this->getTranslationGroupFilePath($translationGroup, $locale);

                // Write file
                $this->writeArrayToFile($translationFile, $translations[$locale]);
            }
        }
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
            ->map(function($item, $key) {
                return pathinfo($item, PATHINFO_FILENAME);
            })
            ->toArray();
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
     * Gets the entries of a specified locale, uses fallback values where necessary.
     *
     * @param string $translationGroup
     * @param string $locale
     * @param array $fallback
     * @return array
     */
    private function getEntriesOrDefault($translationGroup, $locale, $fallback)
    {
        // Read file
        $translation = require($this->getTranslationGroupFilePath($translationGroup, $locale));

        // Get entries
        $entries = [];

        // Loop through default
        foreach ($fallback as $translationKey => $fallbackValue) {
            if (is_array($translation) && array_key_exists($translationKey, $translation)) {
                // Entry exists in the translation, use it
                $entries[$translationKey] = $translation[$translationKey];
            } else {
                // Entry not in translation, use fallback
                $entries[$translationKey] = $fallbackValue;
            }
        }

        return $entries;
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
        return collect(File::directories($path))->map(function($item, $key) {
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
    private function getTranslationGroupFilePath($translationGroup, $locale)
    {
        return $this->getLocalesDirectory() .
            DIRECTORY_SEPARATOR .
            $locale .
            DIRECTORY_SEPARATOR .
            $translationGroup .
            '.php';
    }
}