# RubyWebsite - Changelog

---

## Website API used for NationsGlory Ruby server's staff

---

### Authors :
- Armotik

### Contributors :
- TheKing4012
- bocqueraz

### API Version : 1.0.0

---

```
## 2024-03-24
### Added
- Add : Changelogs folder
- Add : Changelog for 2024-03-24
- Add : NelmoApiDocBundle for documentation generation
- Add : Api composer package for API management
- Add : TokenApiControler for token generation and manipulation
- Add : TokenRevokedException for token revocation management (used in the EventSubscriber)
- Add : UserNotFoundException for user management (used in the EventSubscriber)
- Add : Qodana for code quality analysis (used with PHPStorm when developing)

### Changed
- Change : README.md to include the changelog section
- Change : README.md to include the documentation section
- Change : README.md to include the last update section
- Change : README.md to include the authors and contributors section
- Change : README.md to include the API version

- Change : ApiController -> changes isGranted's requested authorizations to API, not user authorizations
- Change : ApiController -> changes functions to handle CirculaireReferenceException with Token Entity

- Change : ApiImagesController -> changes isGranted's requested authorizations to API, not user authorizations

- Change : EventSubscriber -> add a new subscriber event for authentication and authorization management (TokenRevokedException, UserNotFoundException -> KernelEvents::RESPONSE) 

- Change : ApiTokenPermissionVoter -> add PhpDoc
- Change : ApiTokenPermissionVoter -> now checks if the token is revoked before checking the token's authorizations
- Change : ApiTokenPermissionVoter -> if the user has the ROLE_WEBMASTER, the voter will return ACCESS_GRANTED without checking the token's authorizations

- Change : ApiAuthenticator -> add PhpDoc

- Change : ApiTokenProvider -> add PhpDoc
- Change : ApiTokenProvider -> Check if the token is revoked and if the user linked to the token exists (throw TokenRevokedException, UserNotFoundException)

- Change : StaffAuthenticator -> add PhpDoc

- Change : index_html_twig (staff template) -> modified the template to display the API tokens

- Change : switch to PHP 8.2 (from PHP 8.1) to handle the RandomException in the TokenController (random_int)

### Deprecated

### Removed

### Fixed
- Fix : AppFixtures -> readonly properties for the passwordhasher

### TODO
- TODO : Api documentation with NelmoApiDocBundle
- TODO : Tests for the API

### Known issues
- Issue : The API is not documented and not tested
- Issue : Staff authentication by website is useless for now

### Other

```
