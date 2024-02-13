# Content Security Policy Management Plugin

This plugin allows website administrators to manage their Content Security Policy via a straightforward settings page (requires [CMB2](https://github.com/CMB2/CMB2)) on either the network level, or individual site level. If the plugin is network activated, the CSP will apply to the whole network; if the plugin is single-site activated, the CSP will apply only to that website.

The plugin does not support the addition of hashes to the CSR, due to the complicated implementation in a WordPress environment.

More detail on Content Security Policies can be found at [MDN](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy).

## Minimum Requirements
This plugin requires:  
- WordPress 5.8+  
- PHP 8.2+ with the Intl extension  

## Required Plugins  
We currently rely upon CMB2 and CMB2 extensions for settings management, but our eventual goal is to remove these dependencies.  
Our full list of dependencies is below:  
- [CMB2](https://github.com/CMB2/CMB2)  
- [CMB2 Message Field](https://github.com/amnestywebsite/cmb2-message-field)  

## Import/Export
The plugin allows you to import/export the plugin's options as a JSON file, for ease of migration.

## Supported Directives
The following directives are configurable using this plugin.

### "Global"
Includes Reporting directives, global flags, and other directives that don't fit into another category. The following directives are supported:

**Report URI**  
Sets the reporting destination of CSP violations. The service [Report URI](https://report-uri.com) is recommended for this.

**Report To**  
Configuration which enables the Reporting API. This field accepts a JSON object.

**NEL**  
Configuration which enables Network Error Logging using the Reporting API. This field accepts a JSON object.

**Report Only**  
Whether the CSP should be enforced, or should only report on violations. Useful for testing a CSP configuration prior to implementation.

**Upgrade Insecure Requests**  
Instructs browsers to treat all HTTP URIs as HTTPS.

**Require Trusted Types**  
Experimental. Enforces [Trusted Types](https://w3c.github.io/webappsec-trusted-types/dist/spec/) to prevent DOM XSS injection.

**Allow GTM/GA**  
Register Google's Trusted Type for GTM/GA.

**Enable Script Nonces**  
Experimental. Activates the addition of a [cryptographic nonce](https://en.wikipedia.org/wiki/Cryptographic_nonce) to all `<script>` tags that are added to the DOM server-side, and includes said nonce in the CSP header. So it assumes that all SSR scripts are trusted. This nonce changes with every request; so, either caching needs to be disabled, or request caching needs to be implemented alongside page caching.

### Document
Governs the properties of a document or worker environment to which a policy applies. The following directives are supported:

**Base URI**  
Restricts the URLs to which can be used in a document's `<base>` element.

**Sandbox**  
Enables a sandbox for the requested resource, similar to the `<iframe>` [`sandbox`](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe#attr-sandbox) attribute.

### Navigation
Governs to which locations a user can navigate, or submit a form. The following directives are supported:

**Form Action**  
Restricts the URLs that can be used as the target of a form submission from a given context.

**Frame Ancestors**  
Specifies valid parents that may embed a page using `<frame>`, `<iframe>`, `<object>`, `<embed>`, or `<applet>`. It is recommended that this be set to a value of `'none'`, to disable framing completely.

**Navigate To**  
Restricts the URLs to which a document can initiate navigation — by _any_ means — including `<form>`, `<a>`, `window.location`, `window.open`, etc.

### Fetch Directives
Control the locations from which certain resource types may be loaded. The following fetch directives are supported:

**Default Source**  
Serves as a fallback for the other directives.

**Connect Source**  
Restricts the URLs which can be loaded using script interfaces.

**Font Source**  
Specifies valid sources for fonts loaded using `@font-face`.

**Frame Source**  
Specifies valid sources for nested browsing contexts loaded using element such as `<frame>` and `<iframe>`

**Image Source**  
Specifies valid sources of images and favicon.

**Manifest Source**  
Specifies valid sources of application manifest files.

**Media Source**  
Specifies valid sources for loading media using the `<audio>`, `<video>`, and `<track>` elements.

**Object Source**  
Specifies valid sources for the `<object>`, `<embed>` and `<applet>` elements. _"None" is the recommended setting for this source_.

**Prefetch Source**  
Specifies valid sources to be prefetched or pre-rendered.

**Script Source**  
Specifies valid sources for JavaScript.

**Script Source Attribute**  
Specifies valid sources for JavaScript inline event handlers.

**Script Source Element**  
Specifies valid sources for JavaScript `<script>` elements.

**Style Source**  
Specifies valid sources for stylesheets.

**Style Source Attribute**  
Specifies valid sources for inline styles applied to individual DOM elements.

**Style Source Element**  
Specifies valid sources for stylesheets, `<style>` elements and `<link>` elements with `rel="stylesheet"`.

**Worker Source**  
Specifies valid sources for `Worker`, `SharedWorker`, or `ServiceWorker` scripts.

## Supported Values for Directives

### Toggle-able Options
These options are on/off.

**None**  
Won't allow loading of any resources. Takes precedence, and is incompatible with other options.

**Self**  
Only allow resources from the current origin.

**Strict Dynamic**  
The trust granted to a script in the page due to an accompanying nonce or hash is extended to the scripts it loads.

**Report Sample**  
Require a sample of the violating code to be included in the violation report.

**Unsafe Inline** _not recommended_  
Allow use of inline resources.

**Unsafe Eval** _heavily not recommended_  
Allow use of dynamic code evaluation such as eval, setImmediate, and window.execScript.

**Unsafe Hashes** _not recommended_  
Allows enabling specific inline event handlers.

### Freeform Input Options
These options are for user input.

**Domains**  
Allow loading of resources from a specific host or hosts, with optional scheme, port, and path.

## Usage
The quickest way to get started using the plugin is to download the zip of the [latest release](https://github.com/amnestywebsite/humanity-content-security-policy/releases/latest), and install it via upload directly within WP Admin -> Plugins.  

## Governance
See [GOVERNANCE.md](GOVERNANCE.md) for project governance information.  

## Changelog  
See [CHANGELOG.md](CHANGELOG.md) or [Releases page](https://github.com/amnestywebsite/humanity-content-security-policy/releases) for full changelogs.

## Contributing
For information on how to contribute to the project, or to get set up locally for development, please see the documentation in [CONTRIBUTING.md](CONTRIBUTING.md).  

### Special Thanks
We'd like to say a special thank you to these lovely folks:

| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[Cure53](https://cure53.de)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[WP Engine](https://wpengine.com)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; |
| --- | --- |
| ![Cure53](docs/static/cure_53_logo.svg) | ![WP Engine](docs/static/wpengine_logo.svg) |


### Want to know more about the work in other Amnesty GitHub accounts?

You can find repositories from other teams such as [Amnesty Web Ops](https://github.com/amnestywebsite), [Amnesty Crisis](https://github.com/amnesty-crisis-evidence-lab), [Amnesty Tech](https://github.com/AmnestyTech), and [Amnesty Research](https://github.com/amnestyresearch/) in their GitHub accounts

![AmnestyWebsiteFooter](https://wordpresstheme.amnesty.org/wp-content/uploads/2024/02/footer.gif)
