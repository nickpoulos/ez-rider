<p align="center"><img src="readme.jpg" width="100%"></p>

<p align="center">
  <a href="https://github.com/laravel-zero/framework/actions"><img src="https://img.shields.io/github/workflow/status/laravel-zero/framework/Tests.svg" alt="Build Status"></img></a>
  <a href="https://packagist.org/packages/laravel-zero/framework"><img src="https://img.shields.io/packagist/dt/laravel-zero/framework.svg" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/laravel-zero/framework"><img src="https://img.shields.io/packagist/v/laravel-zero/framework.svg?label=stable" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/laravel-zero/framework"><img src="https://img.shields.io/packagist/l/laravel-zero/framework.svg" alt="License"></a>
</p>

<p>EzRider is a php-cli command (packaged as a PHAR) that provides an easy way to generate Docker Compose Override files for your applications.</p>
Many times your application may require secrets or other sensitive information, perhaps even randomly generated data. By including certain annotations in your Docker Compose files + an ezrider.json config file, Ez Rider will fetch/generate this data, and write the proper override file automatically.  

<p>You can think of this as akin to Vault annotations in K8s, which was an inspiration for this package and its annotation syntax.</p>

<small>Created and maintained by [Nick Poulos](https://nickpoulos.info)</small>

-----

### Development

- Built using [Laravel Zero](https://laravel-zero.com) - a great little distro of Laravel for building and packaging PHP-CLI applications using our favorite PHP framework
- Includes Plugins for Vault, and some random data generators
- Plugins are easily created, please submit a PR!

------

## Quick Start

1. Install this package globally via Composer, NOT from within your project source

```bash
compose global require nickpoulos/ezrider
```

2. In your project's Docker Compose file, create a service containing an environment variable using the syntax below:

docker-compose.yml
```yaml
version: '3.3'

services:
  our-new-api:
    environment:
      APP_KEY: ezrider:string/16
      OTHER_SECRET: vault:secret/data/path/to/vault#value
```

3. In your project's root folder (or wherever docker-compose.yml is located), run: 

```bash
./ez-rider
```

4. You should see two files generated, the default config file `ezrider.json` and your `docker-compose.overrides.yml` file. 


## License

EzRider is an open-source software licensed under the MIT license.
