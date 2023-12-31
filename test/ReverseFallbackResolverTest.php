<?php

declare(strict_types=1);

namespace pine3ree\test\Plates;

use League\Plates\Engine;
use League\Plates\Exception\TemplateNotFound;
use League\Plates\Template\Template;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use pine3ree\Plates\Template\ResolveTemplatePath\ReverseFallbackResolveTemplatePath;

use function realpath;

final class ReverseFallbackResolverTest extends TestCase
{
    private Engine $engine;

    private Template $template;

    private vfsStreamDirectory $appRoot;

    private ReverseFallbackResolveTemplatePath $resolver;

    /**
     * set up test environmemt
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->appRoot = vfsStream::setup('app');

        vfsStream::create([
            'templates' => [
                'blog' => [
                    'post' => [
                        'index.phtml' => 'GLOBAL/blog/post/index',
                        'edit.phtml'  => 'GLOBAL/blog/post/edit',
                        'show.phtml'  => 'GLOBAL/blog/post/show',
                    ],
                ],
                'orphan.phtml' => 'GLOBAL/orphan',
            ],
            'Blog' => [
                'templates' => [
                    'post' => [
                        'index.phtml' => 'LOCAL/blog/post/index',
                        'read.phtml'  => 'LOCAL/blog/post/read',
                        'show.phtml'  => 'LOCAL/blog/post/show',
                    ],
                ],
            ],
        ]);

        $this->resolver = new ReverseFallbackResolveTemplatePath();

        $this->engine = new Engine(vfsStream::url('app/templates'));
        $this->engine->addFolder('blog', vfsStream::url('app/Blog/templates'));
        $this->engine->setFileExtension('phtml');
        $this->engine->setResolveTemplatePath($this->resolver);
    }

    public function testThatFolderTemplateNotationReturnsIdenticalResults(): void
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

    public function testThatGlobalDirectoryTakesPrecedenceOverFolder(): void
    {
        self::assertSame(
            'GLOBAL/blog/post/index',
            $this->engine->render('blog/post/index')
        );
    }

    public function testThatTemplateSpecificFolderIsUsedAsFallback(): void
    {
        self::assertSame(
            'LOCAL/blog/post/read',
            $this->engine->render('blog/post/read')
        );
    }

    public function testThatTemplateWithoutFolderAndSegmentsIsResolved(): void
    {
        self::assertSame(
            'GLOBAL/orphan',
            $this->engine->render('orphan')
        );
    }

    public function testThatCachedResolvesTemplatesReturnTheSamePath(): void
    {
        self::assertSame(
            $this->engine->render('blog/post/index'),
            $this->engine->render('blog/post/index')
        );
        self::assertSame(
            $this->engine->render('blog/post/read'),
            $this->engine->render('blog::post/read')
        );
        self::assertSame(
            $this->engine->render('blog::post/edit'),
            $this->engine->render('blog::post/edit')
        );
    }

    public function testUnresolvedTemplateRaisesTemplateNotFoundExeception()
    {
        $this->expectException(TemplateNotFound::class);
        $this->engine->render('blog/post/non-existent');
    }
}
