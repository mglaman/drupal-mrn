# Drupal maintainer release notes
This is an app for generating release notes for projects hosted on Drupal.org.

Features:

* Extracts contributor information from Drupal.org's commit format for recognition
* Group issues by their type to easily show bug fixes and new features
* Automatically detects previous release version when selecting the target version

## Example

The following request gets the release notes for the Token module's 8.x-1.11 release.

```http request
GET https://qzr5qeis20.execute-api.us-east-1.amazonaws.com?project=token&from=8.x-1.10&to=8.x-1.11
```

## Contributing

@TODO HOW TO WORK LOCAL
