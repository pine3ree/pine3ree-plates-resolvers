<?php

namespace pine3ree\Plates\Template\ResolveTemplatePath;

use League\Plates\Exception\TemplateNotFound;
use League\Plates\Template\Name;
use League\Plates\Template\ResolveTemplatePath;

use function is_file;

/**
 * Works the same way as the default Plates template-path resolver, but with
 * added cache support for resolved templates
 *
 * The internal cache stores and returns by name those template paths that have
 * already been positively resolved, thus avoiding calling `$name->getPath()`
 * repeatedly on the same template/partial
 *
 * This is useful in cases when you use partials like sorting-table-headers links
 * or multiple paginators on the same page
 */
final class NameAndFolderResolveTemplatePath implements
    ResolveTemplatePath,
    CacheableResolveTemplatePathInterface
{
    use CacheableResolveTemplatePathTrait;

    public function __invoke(Name $name): string
    {
        $templateName = $name->getName();
        $templatePath = $this->getFromCache($templateName);
        if (!empty($templatePath)) {
            return $templatePath;
        }

        $templatePath = $name->getPath();
        if (is_file($templatePath)) {
            $this->addToCache($templateName, $templatePath);
            return $templatePath;
        }

        throw new TemplateNotFound(
            $templateName,
            [$templatePath],
            "The template '{$templateName}' could not be found at '{$templatePath}'"
        );
    }
}
