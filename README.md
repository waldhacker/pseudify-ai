![](src/assets/images/pseudify-logo.svg)

[![Build](https://github.com/waldhacker/pseudify-ai/actions/workflows/build-and-release.yml/badge.svg)](https://github.com/waldhacker/pseudify-ai/actions/workflows/build-and-release.yml)

# pseudify AI - the database pseudonymizer

**Pseudify** is a AI powered toolbox that helps you to pseudonymize database data.  
You can find hidden personally identifiable information (PII) in your database and you can pseudonymize them.  

&#127881; Analyze and pseudonymize supported databases from any application  
&#127881; Find hidden personally identifiable information (PII) with or without AI support  
&#127881; Data integrity: same input data generates same pseudonyms across all database columns  
&#127881; Analyze and pseudonymize easily encoded data  
&#127881; Analyze and pseudonymize multi-encoded data  
&#127881; Analyze and pseudonymize complex data structures like JSON or serialized PHP data  
&#127881; Analyze and pseudonymize dynamic data  
&#127881; 12 built-in decoders / encoders  
&#127881; Extensibility with custom decoders / encoders  
&#127881; 100+ built-in localizable fake data formats thanks to [FakerPHP](https://fakerphp.github.io/)  
&#127881; Extensibility with own fake data formats  
&#127881; Support for 7 built-in database platforms thanks to [Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html)  
&#127881; Extensibility with own database platforms  
&#127881; Modeling of profiles with a powerful GUI  

[See the documentation for more information](https://www.pseudify.me/docs/current/)

## Install

The easiest way to run a pseudify showcase is to use [Docker Compose](https://docs.docker.com/compose/).  

Go to some empty directory.  
Download the [`install package`](https://github.com/waldhacker/pseudify-ai/releases/latest/) and unpack it in the current directory:  

```shell
$ docker run --rm -it -v "$(pwd)":/install -w /install -u $(id -u):$(id -g) alpine/curl /bin/sh -c "\
    curl -fsSL https://github.com/waldhacker/pseudify-ai/releases/latest/download/install-package.tar.gz -o install-package.tar.gz \
    && tar -xzf ./install-package.tar.gz \
    && rm -f ./install-package.tar.gz \
"
```

Then start pseudify:

```shell
$ docker compose -f docker-compose.yml -f docker-compose.database.yml up -d
```

Go to your browser an open [http://127.0.0.1:9669](http://127.0.0.1:9669)

[See the documentation for more information and installation variants](https://www.pseudify.me/docs/current/setup/installation/)
