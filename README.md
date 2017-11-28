# Deploy plugin for Z

Provides deployment tools.

## Usage

### Setting up Z

See the documentation for `zicht/z` for more information on how to install Z
with plugins.

### General approach
_NOTE_ this setup requires a few other plugins. You are not required to use those,
but this is what a typical setup would look like (following this deployment scheme).

The general approach for this implementation is:

* A build is created in a separate folder by cloning the current working directory
  into a separate `build` directory (`git` plugin handles this)
* All other plugins have the chance to hook into the build by attaching themselves
  to the `build`'s `post` trigger (common Z functionality, you can hook into any task
  like this)
* An rsync is executed to a remote SSH environment (`rsync` plugin handles this)
* Do more housekeeping in the `deploy`'s `post` trigger.

The `deploy` plugin also provides a `simulate` task, which creates a build and runs
the `rsync` with a `--dry-run` flag, so you can see what would be synced.

### Add exclude file to your project

By default, the `rsync` plugin expects you to add an rsync file to your project:

```
echo ".git/" >> ./rsync.exclude
git add rsync.exclude
git commit -m"add rsync.exclude" ./rsync.exclude
```

Then, you will require a z file in your project:

```yml
plugins: ['env', 'build', 'deploy', 'git', 'rsync']


envs:
    production:
        ssh: myuser@production-machine
        root: ~/my-project-path
        web: web # the relative path to the web folder within the project path

tasks:
    build:
        post:
            - echo "I am just adding a random file here" >> $(path(build.dir, "foo.html"))

    deploy:
        post:
            - echo "Thank you, come again"
```

If you explain the deploy, you would see every step being explained in bash. Read more
about what `explain` does in the documentation for Z.

_Note_: if you'd omit the version here, the repository must be initialized, because by
default the HEAD would be deployed. If you do specify the version, the plugin does
not need a working git directory, because it would just assume that you know what exact
git ref should be deployed:

```
$ z --explain deploy production 1.0.0
echo 'echo "Checking out version foo to ./build";' | /bin/bash -e
echo 'git clone . ./build' | /bin/bash -e
echo 'cd ./build && git checkout foo' | /bin/bash -e
echo 'cd ./build && git log HEAD -1 > .z.rev' | /bin/bash -e
echo 'echo "I am just adding a random file here" >> ./build/foo.html' | /bin/bash -e
echo 'rsync \
     \
    -rpcl --delete  \
        --exclude-from=./build/rsync.exclude \
        -v \
    ./build/ myuser@production-machine:~/my-project-path/ \
;' | /bin/bash -e
echo 'echo "Thank you, come again"' | /bin/bash -e
```

As you can see, each line within this explanation reflects a step in the build
process.

Now, you can also simulate the deploy, which would execute everything except
for the deploy `post` triggers:

```
z simulate production
```

And if that succeeds:

```
z deploy production
```

### Common issues you should check:

* Can you login to the SSH remote? Check this with `z env:ssh production`. It
  is advisable to publish your key to the remote using `z env:ssh-copy-id
  production` you you can access the remote passwordless.
* Does the remote directory exist? `env` tries to open the ssh session within
  the remote directory. Use this to check your setup: `z env:ssh production
  pwd` (or leave out `pwd` to run it interactively).
* Is the rsync file available in the build? You can create the build and
  inspect it yourself

Note that you can always use `explain` to introspect what the plugins and/or
Z try to do. To get even more information, you can combine `--explain` and 
`--debug`, you can see where certain task lines originate from.

## Considerations
* You can add your own mix of plugins to prepare javascript/css files,
  add credentials to your build, etc, etc. However, a lot of common usage
  plugins are already available. These include things like:
  * npm, bower, typescript, babel, etc
  * sass, post-css
  * chmod
* As soon as you find you are copying-and-pasting your z.yml files across
  projects, you should consider creating plugins.

# Maintainers
* Philip Bergman <philip@zicht.nl>
* Michael Roterman <michael@zicht.nl>
