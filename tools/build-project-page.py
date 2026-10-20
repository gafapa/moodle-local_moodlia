"""Build the repository's dependency-free GitHub Pages documentation portal."""

from pathlib import Path
import html
import json
import re
import shutil
from urllib.parse import urlsplit


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / '.project-pages'


def inline(text):
    text = html.escape(text)
    text = re.sub(r'`([^`]+)`', r'<code>\1</code>', text)
    return re.sub(r'\*\*([^*]+)\*\*', r'<strong>\1</strong>', text)


def build():
    config = json.loads((ROOT / 'docs/project-page.json').read_text(encoding='utf-8'))
    readme = (ROOT / 'README.md').read_text(encoding='utf-8-sig')
    title = readme.splitlines()[0].removeprefix('# ').strip()
    status = re.search(r'^## Status\n\n(.+?)(?=\n\n|\Z)', readme, re.M | re.S)
    if status is None:
        raise ValueError('README must have a current Status section')
    for key in ('repository', 'website', 'pages_url'):
        parsed = urlsplit(config[key])
        if parsed.scheme != 'https' or not parsed.netloc or parsed.username:
            raise ValueError(f'{key} must be an absolute HTTPS URL')
    if config['website'] != 'https://moodlia.com':
        raise ValueError('Keep the central MoodlIA website link')
    OUTPUT.mkdir(exist_ok=True)
    source = config['repository']
    website = config['website']
    description = html.escape(config['description'], quote=True)
    steps = ''.join('<li>' + inline(step) + '</li>' for step in config['steps'])
    projects = ''.join('<li><a href="' + html.escape(project['url'], quote=True) + '">' + html.escape(project['title']) + '</a></li>' for project in config['projects'])
    command = '<pre><code>' + html.escape(config['command']) + '</code></pre>' if config.get('command') else ''
    policy = '<a href="' + config['privacy_url'] + '">Extension privacy policy</a>' if config.get('privacy_url') else '<a href="https://moodlia.com/privacy">MoodlIA website privacy</a>'
    page = f'''<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="{description}">
<meta name="color-scheme" content="light">
<meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'self'; font-src 'self'; script-src 'self'; img-src 'self'; base-uri 'none'; form-action 'none'">
<title>{html.escape(title)} — MoodlIA</title>
<link rel="canonical" href="{html.escape(config['pages_url'], quote=True)}">
<link rel="stylesheet" href="page.css">
<script src="page.js" defer></script>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="frame"><a class="brand" href="{website}">MoodlIA</a><nav aria-label="Main navigation"><a href="#start">Getting started</a><a href="{source}">GitHub</a><a href="{website}">moodlia.com</a></nav></header>
<main id="main">
<section class="hero frame" aria-labelledby="title"><h1 id="title">{html.escape(title)}</h1><p class="lead">{description}</p><a class="action" href="{source}#readme">Read the documentation <span aria-hidden="true">↗</span></a></section>
<section class="status" aria-labelledby="status"><div class="frame columns"><h2 id="status">Project status</h2><p>{inline(status[1])}</p></div></section>
<section class="frame start" id="start" aria-labelledby="start-title"><h2 id="start-title">Getting started</h2><div><ol>{steps}</ol>{command}<a class="text-link" href="{source}#getting-started">Full setup and requirements <span aria-hidden="true">↗</span></a></div></section>
<section class="frame continue" aria-labelledby="continue-title"><h2 id="continue-title">Keep going</h2><div class="reading-links"><a href="{source}#usage">Usage and limitations</a><a href="{source}#development">Development and checks</a><a href="{source}#security-and-privacy">Security and privacy</a><a href="{source}/issues">Report an issue</a></div></section>
<section class="directory" aria-labelledby="directory-title"><div class="frame"><h2 id="directory-title">Part of MoodlIA</h2><p>Find tools for course authoring, assessment, analysis and reuse.</p><a class="action" href="{website}/apps">Explore the tools on moodlia.com <span aria-hidden="true">↗</span></a><details><summary>Browse project documentation</summary><div class="filter" hidden><label for="project-search">Find a project</label><input id="project-search" type="search" autocomplete="off" placeholder="Try rubrics, sync or analyzer"><p id="project-count" role="status" aria-live="polite"></p></div><ul id="project-list">{projects}</ul></details></div></section>
</main>
<footer class="frame"><a class="brand" href="{website}">MoodlIA</a><p>{html.escape(config['license'])}. <a href="{source}/blob/main/LICENSE">License and notices</a></p>{policy}<p>This is a project documentation portal. Available releases and qualification limits are recorded in the repository.</p></footer>
</body></html>
'''
    (OUTPUT / 'index.html').write_text(page, encoding='utf-8')
    (OUTPUT / '.nojekyll').write_text('', encoding='utf-8')
    for name in ('page.css', 'page.js', 'roundo-400.woff2', 'roundo-600.woff2', 'font-notice.md'):
        shutil.copyfile(ROOT / 'docs/project-page-assets' / name, OUTPUT / name)
    print(f'Built {title}: {OUTPUT / "index.html"}')


if __name__ == '__main__':
    build()
