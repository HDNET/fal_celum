# EXT:fal_celum - Current state is WIP

> Connect TYPO3 with Celum!

## Improvements related to EXT:celum_connect_fal

- Open Repository
- More separation of concern
- Easy to read Driver (remove the "Read only functions")
- Better Composer/Git structure
- Better Logging configuration
- Better caching configuration
- DI Container configuration
- No Session state for Celum Client
- Move licenseKey decrypt to class
- Drop useless "instance" for logging (logging has already a random request ID)
- Fix "ext_emconf.php" (DO NOT USE STATIC Extension Key there!)
- Remove useless "storage" in Celum client (not needed in client)

## ToDo:

- Configuration Object
- Running version
- Runtime Cache
