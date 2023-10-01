<?php

/**
 * @package pine3ree-plates-resolvers
 * @author  pine3ree https://github.com/pine3ree
 * @copyright (c) 2023, pine3ree
 * @license https://github.com/pine3ree/pine3ree-plates-resolvers/blob/master/LICENSE.md BSD 3-Clause License
 */

namespace pine3ree\Plates\Template\ResolveTemplatePath;

/**
 * Implements CacheableResolveTemplatePathInterface methods
 */
trait CacheableResolveTemplatePathTrait
{
    /**
     * A map of positively resolved template-paths indexed by template-name
     *
     * @var array|string[]|array<string, string>
     */
    protected array $cache = [];

    public function addToCache(string $templateName, string $templatePath): void
    {
        $this->cache[$templateName] = $templatePath;
    }

    public function getFromCache(string $templateName): ?string
    {
        return $this->cache[$templateName] ?? null;
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * @internal Not part of the interface
     *
     * @return array|string[]|array<string, string>
     */
    public function getCache(): array
    {
        return $this->cache;
    }
}
