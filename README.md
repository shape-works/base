# Shape Works base plugin.

Our MU plugin which handles default stuff that all projects share.

## To make changes

*Prior to making changes here, test them directly in the **base** plugin inside your local **shape-dot-works** installation.*

To update the plugin, open terminal in the root directory of the repo and use the following commands:

1. `git tag v2.11` consult latest tag number here https://github.com/shape-works/base/tags
2. `git add .` stages all your changes
3. `git commit -m "Your commit message goes here"`
4. `git push origin v2.11`
5. Also push on GH Desktop app

Now you can run `composer update shape-dot-works/base` from your **shape-dot-works** installation.
