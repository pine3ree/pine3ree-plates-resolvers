<?php

/**
 * @package pine3ree-plates-resolvers
 * @author  pine3ree https://github.com/pine3ree
 * @copyright (c) 2023, pine3ree
 * @license https://github.com/pine3ree/pine3ree-plates-resolvers/blob/master/LICENSE.md BSD 3-Clause License
 */

namespace pine3ree\Plates\Template\ResolveTemplatePath;

/**
 * Memoize resolved template-paths
 */
interface CacheableResolveTemplatePathInterface
{
    /**
     * Add cache entry for resolved template-path with given name
     *
     * @param string $templateName The template name/identifier
     * @param string $templatePath The template filesystem path
     * @return void
     */
    public function addToCache(string $templateName, string $templatePath): void;

    /**
     * Get a resolved template path by name or NULL if not found
     *
     * @param string $templateName The template name/identifier
     * @return string|null
     */
    public function getFromCache(string $templateName): ?string;

    /**
     * Empty the resolved templates cache
     *
     * @return void
     */
    public function clearCache(): void;
}
