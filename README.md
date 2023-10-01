# pine3ree-plates-resolvers

This package provides a couple of template resolvers with cache support for the
Plates engine:

- `NameAndFolderResolveTemplatePath` is the same default Plates resolver, but
   with cache enabled

- `ReverseFallbackResolveTemplatePath` is a resolver that works in the opposite
  way: first, if defined, the global template directory is searched and then
  the template folder is searched

### Install

This package requires `PHP 7.4 ~ 8.2`. You can install it via composer:

```bash
$ composer require pine3ree/pine3ree-plates-resolvers
```

### NameAndFolderResolveTemplatePath

Works the same way as the default Plates template-path resolver, but with
added cache support for resolved templates.

The internal cache stores and returns by name those template paths that have
already been positively resolved, thus avoiding calling `$name->getPath()`
repeatedly on the same template. The internal cache is enabled including the
`CacheableResolveTemplatePathTrait trait` in this package implementing methods
of the `CacheableResolveTemplatePathInterface`

This is useful in cases when you use partials like sorting-table-headers links
or multiple paginators several times on the same page. At the same time it also
provides long term caching for async environments like `Swoole`

### ReverseFallbackResolveTemplatePath


This resolver acts in the opposite way of the default plates resolver

When a template is rendered with a folder specification (Plates `::` notation),
the search starts at the default template directory (if defined) but with an
added sub-folder matching the folder specification, e.g.:

```php
$template->render('partials::pagination', $vars);

// The search order is:
//
// 1. {/path/to/the/default/templates/directory/}partials/pagination.phtml
// 2. {/path/to/the/partials/template/folder/}pagination.phtml
```

Furthermore, when a template name is provided without a folder specification but
contains the path separator "/", then a folder will be assigned using the first
segment. Therefore the previous example applies also for the following simpler
render call:

```php
$template->render('partials/pagination', $vars);
```
In both cases the "partial" folder must have been defined in the engine's
configuration.

This can be useful in modular application, where we want each module templates
to be close to the source code. In those cases we set a folder for each module
template. When we reuse the same module in other applications it will works
right away. Then, we will use the default "application" templates directory t0
customize/override the default templates provided by each module.

Example:

Given the following application directory structure

```

/path/to/my/web/app/
    templates/ <-- global templates directory
        news/
            article/
                read.phtml <-- This template will be LOADED
                ...
        shop/
            product/
                <-- no index.phtml here -->
                ...

    News/ <-- module dir
        Controller/
            Article/
                ReadController.php
        Model/
        etc/
        templates/ <-- local module templates folder
            article/
                read.phtml <-- This template will be DISCARDED
                ...
    Shop/ <-- module dir
        Controller/
            Product/
                IndexController.php
        Model/
        etc/
        templates/ <-- local module templates folder
            product/
                index.phtml <-- This template will be LOADED
                ...
```

The following code applies:

```php
$plates = new League\Plates\Engine('/path/to/my/web/app/templates');

$plates->addFolder('news', '/path/to/my/web/app/News/templates');
$plates->addFolder('shop', '/path/to/my/web/app/Shop/templates');

// This will load the template in the global templates directory, because a
// matching template-file is found
$plates->render("news::article/read", $vars); // or simply
$plates->render("news/article/read", $vars);

// This will load the template in the module templates directory, because there
// is no matching file in the global directory
// This will load the template in the module templates directory
$plates->render("shop::product/index", $vars); // or simply
$plates->render("shop/product/index", $vars);

```

The url-path "/" segment notation is recommended since it shows the template
path relative to the global templates directory, while the standard folder
notation could trick into believeing that global directory templates are not
being used.
