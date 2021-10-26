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
      APP_KEY: random:string(64)
      APP_ENV: random:array(local, sand, prod)
      APP_PORT: random:int(1,1000)
      OTHER_SECRET: vault:secret/data/path/to/vault#value
```

3. In your project's root folder (or wherever docker-compose.yml is located), run: 

```bash
./ez-rider
```


4. You should see two files generated, the default config file `ezrider.json` and your `docker-compose.overrides.yml` file.


5. The config file should be committed as part of source control with your repo, and contains the following options:

```json
{
    "plugin_paths": [],
    "plugins": [
        "VaultRetriever", "RandomGenerator"
    ],
    "map": [
        {
            "input": "docker-compose.yml",
            "output": "docker-compose.override.yml"
        }
    ]
}
```

- `plugin_paths`: array of folders to check for plugin files (empty by default, merged with internal plugins path)


- `plugins`: array of class names that correspond to the plugin you want to load from `plugin_paths`. These plugins will be applied to generate your override. If your docker-compose does not use any annotations from a particular plugin, feel free to remove that plugin here


- `map`: array of map objects that tell which docker-compose files to map, and their output filename\

## Vault Plugin
The HashiCorp Vault Plugin connects to Vault servers via an API.   It requires a Vault Base Url and Token to operate.  

The plugin will prompt you for this info and is cached for subsequent calls. Unless you have either of the following environment vars set, in which case the prompt is skipped and this value used. For example: 

```bash
export VAULT_URL=http://our-vault-server.vault.com
export VAULT_TOKEN=abc1234
```



## License

EzRider is an open-source software licensed under the MIT license.
