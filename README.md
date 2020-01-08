# Plates Integration for Mezzio

[![Build Status](https://travis-ci.com/mezzio/mezzio-platesrenderer.svg?branch=master)](https://travis-ci.com/mezzio/mezzio-platesrenderer)
[![Coverage Status](https://coveralls.io/repos/github/mezzio/mezzio-platesrenderer/badge.svg?branch=master)](https://coveralls.io/github/mezzio/mezzio-platesrenderer?branch=master)

Provides integration with [Plates](http://platesphp.com/) for
[Mezzio](https://github.com/mezzio/mezzio).

## Installation

Install this library using composer:

```bash
$ composer require mezzio/mezzio-platesrenderer
```

We recommend using a dependency injection container, and typehint against
[container-interop](https://github.com/container-interop/container-interop). We
can recommend the following implementations:

- [laminas-servicemanager](https://github.com/laminas/laminas-servicemanager):
  `composer require laminas/laminas-servicemanager`
- [pimple-interop](https://github.com/moufmouf/pimple-interop):
  `composer require mouf/pimple-interop`
- [Aura.Di](https://github.com/auraphp/Aura.Di)

## Documentation

See the Mezzio [Plates documentation](https://docs.mezzio.dev/mezzio/features/template/plates/).
