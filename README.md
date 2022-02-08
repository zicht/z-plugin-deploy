# Z-plugin to provide deploy tasks

This plugin provides tasks to build and deploy the current project:

* `deploy` task to build and deploy the project to a specific environment
* `simulate` task to build and simulate a deploy of the project against a specific environment
* `redeploy` task to rebuild and redeploy the version of the project currently on a specific environment
* `patch` task to patch a specific environment
* `unpatch` task to revert a patch on a specific environment
* `qdeploy` task to build the project and patch a specific environment
* `qsimulate` task to build the project and simulate patching a specific environment

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
    prod:
        ssh: myuser@prod-machine
        root: ~/my-project-path
        web: public    # the relative path to the public web folder within the project path

tasks:
    build:
        post:
            - echo "I am just adding a random file here" >> $(path(build.dir, "foo.html"))

    deploy:
        post:
            - echo "Thank you, come again"
```

If you explain the deploy, you would see every step being explained in bash. Read more
about what `--explain` does in the documentation for Z.

```
$ z --explain deploy prod
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
z simulate prod
```

And if that succeeds:

```
z deploy prod
```

### Common issues you should check:

* Can you login to the SSH remote? Check this with `z env:ssh prod`. It
  is advisable to publish your key to the remote using `z env:ssh-copy-id
  prod` you you can access the remote passwordless.
* Does the remote directory exist? `env` tries to open the ssh session within
  the remote directory. Use this to check your setup: `z env:ssh prod pwd`
  (or leave out `pwd` to run it interactively).
* Is the rsync file available in the build? You can create the build and
  inspect it yourself

Note that you can always use `--explain` to introspect what the plugins and/or
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

# Maintainer(s)
* Jochem Klaver <jochem@zicht.nl>
