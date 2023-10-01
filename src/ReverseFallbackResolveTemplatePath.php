<?php

namespace pine3ree\Plates\Template\ResolveTemplatePath;

use League\Plates\Exception\TemplateNotFound;
use League\Plates\Template\Name;
use League\Plates\Template\ResolveTemplatePath;

use function explode;
use function implode;
use function is_file;
use function sprintf;

/**
 * This resolver acts in the opposite way of the default plates resolver
 *
 * When a template is rendered with a folder specification, the search starts
 * at the default template directory (if assigned) but with an added sub-folder
 * matching the folder specification, e.g.
 *
 * ```php
 * $template->render('partials::pagination', $vars);
 * // The search order is:
 * // 1. /path/to/default/directory/partials/pagination.phtml
 * // 2. /path/to/template/folder/pagination.phtml
 * ```
 *
 * When a template name is provided without a folder specification but contains
 * the path separator "/", then a folder will be assigned using the first segment,
 * so previous example is valid also for the following render call:
 *
 * ```php
 * $template->render('partials/pagination', $vars);
 * ```
 * In both cases the "partial" folder must have been defined in the engine's configuration
 *
 * This can be useful in modular application, where we want the module templates
 * to be close to the source code. In those cases we set a folder for each module
 * template. When we reuse the same module in other applications it will works
 * right away. Then, we will use the default "application" templates directory t
 * customize/override the default module templates.
 *
 */
final class ReverseFallbackResolveTemplatePath implements
    ResolveTemplatePath,
    CacheableResolveTemplatePathInterface
{
    use CacheableResolveTemplatePathTrait;

    /**
     * A cache for template names that have already been assigned a folder using
     * the name's first segment
     */
    protected array $processed = [];

    public function __invoke(Name $name): string
    {
        $templateName = $name->getName();
        $templateName = str_replace('::', '/', $templateName); // Force unique cache-key
        $templatePath = $this->getFromCache($templateName);
        if (!empty($templatePath)) {
            return $templatePath;
        }

        // Create a search-path accumulator array
        $templatePaths = [];

        $templateNameIsAbsolutePath = $templateName[0] === '/';

        if ($templateNameIsAbsolutePath) {
            // Handles the case of a full-unix path including the file-extension:
            // "name" provided as "/full/path/to/my/template-name.phtml"
            $templatePaths[] = $templateName;
            // Handles the case of a full-unix path provided w/o the file-extension:
            // "name" provided as "/full/path/to/my/template-name"
            $templatePaths[] = $templateFile = $name->getFile();
        } else {
            $folder = $name->getFolder();
            // If the template does not specify a "::" folder, set a folder using the
            // template-name's first segment before the "/" separator
            if ($folder === null && empty($this->processed[$templateName])) {
                // Only process names that are not unix/linux absolute file paths
                if ($templateNameIsAbsolutePath === false) {
                    $parts = explode('/', $templateName, 2);
                    if (isset($parts[1])) {
                        $name->setFolder($parts[0]);
                        $name->setFile($parts[1]);
                        $folder = $name->getFolder();
                    }
                }
                // Avoid processing the same template twice
                $this->processed[$templateName] = true;
            }

            $templatesDirectoryPath = $name->getEngine()->getDirectory();
            $templateFile = $name->getFile();

            if ($folder) {
                $folderName = $folder->getName();
                if ($templatesDirectoryPath) {
                    $templatePaths[] = "{$templatesDirectoryPath}/{$folderName}/{$templateFile}";
                }
                $templatePaths[] = "{$folder->getPath()}/{$templateFile}";
            } elseif ($templatesDirectoryPath) {
                $templatePaths[] = "{$templatesDirectoryPath}/{$templateFile}";
            }
        }


        // Return the 1st match, if any
        foreach ($templatePaths as $templatePath) {
            if (is_file($templatePath)) {
                $this->addToCache($templateName, $templatePath);
                return $templatePath;
            }
        }

        throw new TemplateNotFound($name->getName(), $templatePaths, sprintf(
            "The template '%s' could not be found at the following paths: ['%s']",
            $templateName,
            implode("', '", $templatePaths)
        ));
    }
}
