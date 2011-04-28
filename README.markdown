SimplePhar
==========

## What's this?

SimplePhar is a command line tool to simplify the generation of PHP Archives.

## Synopsis
```
php /path/to/compile.phar [target]
```

## Prerequisites
* minimum PHP 5.3
* Disabled `phar.readonly` Setting in `php.ini`

## Usage

When `compile.phar` is invoked, it looks for a file named `Pharfile` in the `target` directory.
If no `target` is given, the script looks in the current working directory for the `Pharfile`
This file is written in a `php.ini` style syntax. Let's take a look at SimplePhar's `Pharfile`:

```ini
dist_path    = "bin/compile.phar"
file_paths[] = "src"
cli_stub     = "src/cli.php"
```

The `Pharfile` consists of some simple directives:

<dl>
    <dt>dist_path</dt>
    <dd>Path where the generated PHAR file should be placed</dd>
    
    <dt>file_paths</dt>
    <dd>An <code>Array</code> of Paths relative to the Pharfile's directory. All <code>.php</code> 
    files in these paths are added to the PHAR</dd>
    
    <dt>cli_stub</dt>
    <dd>Path relative to the Pharfile's directory to your Bootstrap file. Make sure that this
    file is located within one of the <code>file_paths</code>.</dd>
    
    <dt>license_file</dt>
    <dd>Path to an optional LICENSE file which should be also included in the PHAR</dd>
</dl>

That's it!

## License

The MIT License

Copyright (c) Christoph Hochstrasser

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
