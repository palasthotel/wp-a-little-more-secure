# CI/CD Workflows

The four workflows in `.github/workflows/` call the shared ones in
[palasthotel/github-workflows](https://github.com/palasthotel/github-workflows). How
they work, every input and what to do when a deploy fails is described there, in
[docs/wp-plugin.md](https://github.com/palasthotel/github-workflows/blob/main/docs/wp-plugin.md).

What is specific to this plugin:

| | |
|---|---|
| wordpress.org slug | `a-little-more-secure` |
| version file | `version.txt` (`release-type: simple`) |
| readme | `public/README.txt` - upper case, the shared scripts find it either way |
| build step | none |
| composer | `public/composer.json` only maps the autoloader, but requires `php ~8.2`, so `pr.yml` and `wordpress-svn-release.yml` set `php-version: "8.2"` for the pack step, and `php -l` runs on 8.2, 8.3 and 8.4 only |
| `assets/` | in the repository, mirrored into the SVN `assets/` on every release - files removed here are removed from the plugin page |
| symlinks | the Swiss translations in `public/languages/` link to `de_DE`; the pack resolves them into files, which is how SVN has always held them |
