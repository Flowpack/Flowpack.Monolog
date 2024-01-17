# Monolog Integration for Flow

Provides a [monolog](https://github.com/Seldaek/monolog) factory to be used with Flow.

❗
> This package replaces all Neos Flow logs (Security, System, Query, I18n) with Monolog by default.
> To change that and the handlers check the Settings.yaml. See also configuration notes below.

👻
> The monolog format is slightly different than the default Flow log file format, 
> also the configured monolog handler does **no log rotation** like the Flow log does, 
> so you need to take care of that.


## Installation

Use composer to install this package:

`composer require flowpack/monolog`

All Framework logs should now be in monolog format and you need to add configuration for any other logs you might want.

## Configuration

You have several ways to configure monolog with this package, the easiest is seen in the configuration for the Neos Flow logs in this package:

```yaml
Neos:
  Flow:
    log:
      psr3:
        'Flowpack\Monolog\LoggerFactory':
          # name of the logger as Flow addresses it, eg. "systemLogger"
          '<name of the logger>':
            handler:
              # unique name for this handler in this log, for extending the configuration
              '<identifier for this handler>':
                className: '<monolog compatible handler class name fully qualified>'
                # sorting for this handler if you want it deterministic with overwrites
                position: 100
                # arguments given to the handler, zero index based, as the handler constructor expects them
                arguments:
                  0: '<the first argument given to the constructor of the handler>'
              # another handler could follow here
```

Another option is to create preset handlers, e.g. if you need to use the same handler with the same configuration in multiple places, no overrides of this default configuration is possible at this time:

```yaml
Flowpack:
  Monolog:
    handler:
      # unique identifier of this handler (preset)
      '<presetName>':
        className: '<monolog compatible handler class name fully qualified>'
        # note that preset handlers currently cannot have a position, they are sorted as configured
        # arguments given to the handler, zero index based, as the handler constructor expects them
        arguments:
            0: '<the first argument given to the constructor of the handler>'

Neos:
  Flow:
    log:
      psr3:
        'Flowpack\Monolog\LoggerFactory':
          # name of the logger as Flow addresses it
          '<name of the logger>':
            handler:
              # the presetName should be the same as in above preset configuration
              '<identifier for this handler>': '<presetName>'
```

⚠️ Note that using presets does not work for loggers needed early in the bootstrap, as the settings
for the Flowpack.Monolog package itself are injected rather late and the preset is only available
afterwards.

## Handlers

For more information about handlers and their configuration check also the monolog [documentation on handlers, formatters & processors](https://seldaek.github.io/monolog/doc/02-handlers-formatters-processors.html).
 
