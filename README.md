AxisCurlyRouting
================

This plugin introduces to symfony 1.x a new route class that uses curly braces in pattern
just like Symfony2 routes.

Installation
------------

Use [Composer](http://getcomposer.org/). Just add this dependency to your `composer.json`:

```json
  "require": {
    "axis/axis-curly-routing-plugin": "dev-master"
  }
```

Usage
-----

Now you can use Curly routes. Just declare routes in your `routing.yml` files with
`CurlyRoute` class specified:

```yaml
curly_route:
  url: /hello/{name}
  param: { module: test, action: sayHello }
  class: CurlyRoute
```

That's all. You can reference this route as you usually do with any symfony route.

For generating URLs:
```php
<?php echo url_for('curly_route', array('name' => 'world')) ?>
```

For routing URLs to controller:
```
class testActions extends sfActions
{
  public function executeSayHello($request)
  {
    return $this->renderText('Hello, ' . $request['name']);
  }
}
```

Sugar
-----
Yeah. There is some new cool things you can do with this routes.

### Hierarchical URLs

One of the main reasons of implementing this routes was the ability to use path variables
in routes. For example you want to use something like hierarchical structure in your urls:

You could do this with default symfony routing:

```yaml
asset:
  url: /:path/:filename.:sf_format
  param: { ... }
  requirements:
    path: .*
```

This works well routing requests from `/my/assets/path/image.png` to defined controller.
But when you need to generate url for that path you'll get this: `/my%2Fassets%2Fpath/image.png`.


Curly routes enable you to use them for this kind of tasks.

```yaml
asset:
  url: /{path}/{filename}.{sf_format}
  param: { ... }
  class: CurlyRoute
  requirements:
    path: .*
    sf_format: \w+
```

### Variables delimited by any symbols

```yaml
blog_post:
  # you cannot use path like '/blog/:slug-:id.html' using default symfony routes
  url: /blog/{slug}-{id}.html
  param: { ... }
  class: CurlyRoute
  requirements:
    slug: .+
    id:   \d+
```

### Propel Object route on steroids

You can handle propel object requests using `CurlyObjectRoute` just like you did it with `sfPropelRoute`.

```yaml
blog_post:
  url: /blog/{slug}-{id}.html
  param: { ... }
  class: CurlyObjectRoute
  options:
    model: BlogPost
    query_methods: [ filterPublished ]
    # Note: there is no 'type' option because CurlyObjectRoute
    #   doesn't support collection routes for now.
  requirements:
    slug: .+
    id:   \d+
```

And you can use this like you did with `sfPropelRoute`. For generating URLs:

```php
<?php echo url_for('blog_post', $post) ?>
```

and for retrieving object from controller:

```php
class blogActions extends sfActions
{
  public function executeShowPost($request)
  {
    $post = $this->getRoute()->getObject();
  }
}
```

#### Namespaces

Sometimes you need to use object properties **plus** some other variables in your URLs.
Now you can use `CurlyObjectRoute` to handle this just defining namespace:

```yaml
blog_post:
  url: /{username}blog/{post.slug}-{post.id}.html
  param: { ... }
  class: CurlyObjectRoute
  options:
    model: BlogPost
    query_methods: [ filterPublished ]
    namespace: post # Note this option
  requirements:
    username: \w+
    post.slug: .+
    post.id:   \d+
```

And usage. For generating URLs:

```php
<?php echo url_for('blog_post', array('post' => $post, 'username' => 'anonymous')) ?>
```

and for retrieving object from controller:

```php
class blogActions extends sfActions
{
  public function executeShowPost($request)
  {
    $post = $this->getRoute()->getObject('post');
  }
}
```

#### Multiple objects per route

Also you can use more than one object in your routes.

```php
show_product:
  url: /shop/{category.path}/{product.slug}-{product.id}.html
  param: { ... }
  class: CurlyObjectRoute
  options:
    transform:
      product:
        model: Product
        query_methods: [ filterPublished, filterInStock ]
      # or you can use short syntax if there is only model option for a namespace
      category: Category
  requirements:
    slug: .+
    id:   \d+
```

And usage. For generating URLs:

```php
<?php echo url_for('show_product', array('category' => $category, 'product' => $product)) ?>
```

and for retrieving object from controller:

```php
class shopActions extends sfActions
{
  public function executeShowProduct($request)
  {
    /** @var $category Category */
    $category = $this->getRoute()->getObject('category');
    /** @var $product Product */
    $product = $this->getRoute()->getObject('product');
  }
}
```

--------------------------
  *Note*:
  To use `CurlyObjectRoute` you should upgrade your project to use
  **Propel 1.6** by installing [PropelORMPlugin](https://github.com/propelorm/sfPropelORMPlugin).

Extending Curly Routes
----------------------
You can use any custom parameters converters with `CurlyRoute`s.
Define them using `transform` option:

```yaml
weird_route:
  url: /say/{weird_word}
  class: CurlyRoute
  options:
    transform:
      weird_transformer:
        class: myProjectWeirdRouteVarTransformer
        # ... any other options
    # or short syntax
    # transform: myProjectWeirdRouteVarTransformer
```

To implement custom parameter transformer in your project create a class that implements
 `\Axis\S1\CurlyRouting\Transformer\DataTransformerInterface`.

```php
class myProjectWeirdRouteVarTransformer implements \Axis\S1\CurlyRouting\Transformer\DataTransformerInterface
{
  public function transformForUrl($params, $variables, $options = array())
  {
    $params['weird_word'] = 'foo'.$params['word'].'bar'
    unset($params['word']);
    return $params;
  }

  public function transformForController($params, $variables, $options = array())
  {
    $weird = $params['weird_word'];
    if (substr($weird, 0, 3) == 'foo') $weird = substr($weird, 3);
    if (substr($weird, -3) == 'bar') $weird = substr($weird, 0, -3);

    unset($params['weird_word']);
    $params['word'] = $weird;
    return $params;
  }
}
```

This transformer takes an array of parameters on input and returns a resulting array
of parameters to be used by route.

The result of `transformForUrl` method will be used when you generate an URL:

```php
This code:
<?php echo url_for('weird_route', array('word' => 'hello')) ?>
will output:
/say/foohellobar
```

On the other hand by navigating to that url (`/say/foohellobar`) the route will fetch `weird_word`
 variable with the value set to `foohellobar`. Than it will be passed through data all your
 route's defined transformers and you'll get the transformed variables in your request and controller:

```php
class weirdActions extends sfActions
{
  public function executeSay($request)
  {
    $this->renderText($request['word']); // this will output 'hello'
  }
}
```

You can do a lot of cool stuff using custom transformers without the need to implement custom routes.
By the way, `CurlyObjectRoute` uses transformers to handle object requests. Look at that class to
find more about params transformers. You can chain them and reuse already implemented code.

Sounds fantastic isn't it?