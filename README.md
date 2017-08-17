# SilverStripe Weblog Module

The SilverStripe weblog module is a simplified adaption of the SilverStripe Blog
[module](https://github.com/silverstripe/silverstripe-blog).

The idea is to start with a basic framework for your blog, and add only the necessary
features when needed.

**This module is still in beta and relies on development versions of SilverStripe 4 & lumberjack.**


## Features

- Basic foundation for Blog and BlogPosts
- A single custom permissions group `CMS_ACCESS_Weblog`
- Scheduled blog posts
- Featured image


## Documentation

- [Installation](docs/en/Installation.md)
- [Configuration](docs/en/Configuration.md)


## Requirements

```
silverstripe/cms: ^4.0
silverstripe/lumberjack: dev-master@dev
```

## Suggested Modules

- [axllent/silverstripe-weblog-categories](https://github.com/axllent/silverstripe-weblog-categories) - Add blog categories to your posts
