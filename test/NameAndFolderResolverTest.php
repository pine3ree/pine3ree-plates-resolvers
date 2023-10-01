<?php

declare(strict_types=1);

namespace pine3ree\test\Plates;

use League\Plates\Engine;
use League\Plates\Exception\TemplateNotFound;
use League\Plates\Template\Template;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use pine3ree\Plates\Template\ResolveTemplatePath\NameAndFolderResolveTemplatePath;

final class NameAndFolderResolverTest extends TestCase
{
    private Engine $engine;

    private Template $template;

    private vfsStreamDirectory $appRoot;

    private NameAndFolderResolveTemplatePath $resolver;

    /**
     * set up test environmemt
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->appRoot = vfsStream::setup('templates');

        vfsStream::create([
            'blog' => [
                'post' => [
                    'index.phtml' => 'RENDERED/blog/post/index',
                    'read.phtml'  => 'RENDERED/blog/post/read',
                    'edit.phtml'  => 'RENDERED/blog/post/edit',
                ],
            ],
        ]);

        $this->resolver = new NameAndFolderResolveTemplatePath();

        $this->engine = new Engine(vfsStream::url('templates'));
        $this->engine->addFolder('blog', vfsStream::url('templates/blog'));
        $this->engine->setFileExtension('phtml');
        $this->engine->setResolveTemplatePath($this->resolver);
    }

    public function testThatBothNotationReturnsIdenticalResultsIfFolderDirectoryIsChildOfGlobalDirectory(): void
    {
        self::assertSame(
            $this->engine->render('blog::post/index'),
            $this->engine->render('blog/post/index')
        );
        self::assertSame(
            $this->engine->render('blog::post/read'),
            $this->engine->render('blog/post/read')
        );
        self::assertSame(
            $this->engine->render('blog::post/edit'),
            $this->engine->render('blog/post/edit')
        );
    }

    public function provideTemplatePairs(): array
    {
        return [
            ['blog/post/index',  'blog/post/index'],
            ['blog::post/index', 'blog::post/index'],
            ['blog/post/index',  'blog::post/index'],
            ['blog::post/index', 'blog/post/index'],
        ];
    }

    /**
     * @dataProvider provideTemplatePairs
     */
    public function testThatCachedResolvesTemplatesReturnTheSamePath(
        string $templateA,
        string $templateB = null
    ): void {
        self::assertSame(
            $this->engine->render($templateA),
            $this->engine->render($templateB ?? $templateA)
        );
    }

    public function testThatUnresolvedTemplateRaisesTemplateNotFoundExeception()
    {
        $this->expectException(TemplateNotFound::class);
        $this->engine->render('blog::post/non-existent');

        $this->expectException(TemplateNotFound::class);
        $this->engine->render('blog/post/non-existent');
    }

    public function testThatGetCacheRetunsExpectedEntries()
    {
        $this->engine->render('blog/post/index');
        $this->engine->render('blog::post/read');

        $expectedCache = [
            'blog/post/index' => 'vfs://templates/blog/post/index.phtml',
            'blog::post/read' => 'vfs://templates/blog/post/read.phtml',
        ];

        self::assertSame($expectedCache, $this->resolver->getCache());
    }

    public function testThatClearCacheWorks()
    {
        $this->engine->render('blog/post/index');
        $this->engine->render('blog::post/read');

        self::assertNotEmpty($this->resolver->getCache());

        $this->resolver->clearCache();

        self::assertEmpty($this->resolver->getCache());
    }
}
