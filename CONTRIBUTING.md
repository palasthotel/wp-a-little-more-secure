# Contributing

## Branching

`main` is the default branch and always reflects what is released (or about to
be released). Work on a feature branch and open a pull request against `main`.

## Commit messages

Releases and the changelog are generated from the commit history, so commit
messages follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>[optional scope][!]: <description>

[optional body]

[optional footer]
```

| Type | Effect on the version | Appears in changelog |
|---|---|---|
| `fix:` | patch (1.0.3 → 1.0.4) | yes, "Bug Fixes" |
| `feat:` | minor (1.0.3 → 1.1.0) | yes, "Features" |
| `feat!:` or `BREAKING CHANGE:` footer | major (1.0.3 → 2.0.0) | yes, highlighted |
| `docs:`, `refactor:`, `chore:`, `deps:`, `style:`, `test:`, `ci:` | none | no |

Examples:

```
fix: reject a non-string nonce before verifying it
feat: allow the unlock parameter to carry a value
feat!: drop the a_little_more_secure_nonce_field() function

BREAKING CHANGE: themes calling a_little_more_secure_nonce_field() must switch
to Plugin::instance()->gate->nonceField()
```

A pull request that should trigger a release needs at least one `fix:` or
`feat:` commit. When squash-merging, make sure the squash commit message itself
is a conventional commit — that is the message release-please reads.

### Which changes get `fix:` or `feat:`

Only changes that matter to someone using the plugin. `fix:` and `feat:` decide
the version *and* write the line that ends up in the changelog on the
wordpress.org plugin page, so the question to ask before committing is whether a
user of the plugin would care about that line.

Everything else takes a type that releases nothing — workflows and CI, release
tooling, repository documentation, internal refactoring, and anything touching
files that are not shipped. As a rule of thumb, a change confined to files
outside `public/` is almost never a `fix:`.

That includes hardening. Blocking direct access to a file that is not part of the
download is `chore:`, not `fix:` — nothing changes for anyone who installed the
plugin.

## Versions

Never edit version numbers by hand. `version.txt`, `CHANGELOG.md`,
`public/a-little-more-secure.php` and the `Stable tag:` in `public/README.txt` are all
maintained by the release pipeline — see
[.github/WORKFLOWS.md](.github/WORKFLOWS.md).

Content changes to `public/README.txt` (description, FAQ, screenshots,
tested-up-to) are of course done by hand; just leave `Stable tag:` and the
`== Changelog ==` entries alone.

## Local development

There is nothing to build or install. wp-env runs without a configuration file and
mounts the repository as the plugin:

```sh
npx @wordpress/env start      # http://localhost:8888, admin / password
```

To test the repository itself as a plugin, symlink or copy the repository root
into `wp-content/plugins/`. WordPress only discovers plugin files one directory
level deep, so it would never find `public/a-little-more-secure.php` — the
`a-little-more-secure.php` wrapper in the root includes it and registers the
activation hooks against the file WordPress actually knows about. It carries
`X.X.X` as its version so it is obvious in the plugin list that this entry is
never released.

`npm run pack` stages the payload in `build/a-little-more-secure/` and zips it to
`a-little-more-secure.zip` — the same payload the release deploys. It runs the shared
script from [palasthotel/github-workflows](https://github.com/palasthotel/github-workflows),
which has to be checked out next to this repository.

The main file `public/a-little-more-secure.php` must keep its name. WordPress identifies an
installed plugin by `<directory>/<main file>` and stores that pair in `active_plugins`;
renaming it deactivates the plugin on every site at the next update.

## Checks

Every PR runs `php -l` against PHP 8.2, 8.3 and 8.4, packs the plugin and checks the
payload, and checks the version carriers agree. The
plugin declares `Requires PHP: 8.2` and `Requires at least: 5.0` (WordPress).
The PHP floor comes from `public/composer.json` (`php ~8.2`), whose generated
`vendor/composer/platform_check.php` turns anything older into a fatal error —
so if you change that constraint, change the plugin header and
`public/README.txt` in the same PR.
