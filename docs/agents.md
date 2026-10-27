# AI assistance

Use the package-owned [`php-forge-diff` skill](../skills/php-forge-diff/SKILL.md) when integrating or troubleshooting
line edit scripts or unified rendering. The canonical source is `skills/php-forge-diff/`; `.agents/skills/` and
`.claude/skills/` link to it for local discovery.

## Install in an application

### Automatic Composer installation

Complete the
[one-time application setup](https://yii2-framework.github.io/docs/project/ai-agents/#composer-installation) to enable
`llm/skills` and set `auto-sync` to `true` in `skills.json`. Explicitly trust `php-forge/diff` in
`dependencies.composer.trusted`. The installed package must include the skill bundle declared by `extra.skills.source`.

```bash
composer install
composer skills:show
```

With `auto-sync` enabled, later dependency installs and updates synchronize the skill. If disabled, run
`composer skills:update` after dependency installation or updates.

### Optional npx installation

From the application root, list the source before installing:

```bash
npx skills@1.7.2 add php-forge/diff --list
npx skills@1.7.2 add php-forge/diff --skill php-forge-diff
```

Choose the intended client. Installation, discovery, native loading and successful task behavior are distinct outcomes;
verify each independently. Installing a skill does not install Composer dependencies. Prefer the installed source and
guides at the same revision; report when matching guides are unavailable.

## Next steps

- 💡 [Usage examples](examples.md)
- 🧪 [Testing guide](testing.md)

[Back to documentation](../README.md#documentation)
