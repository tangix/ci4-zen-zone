# git over ssh problems after macOS Ventura update

Upgraded my machine to macOS Ventura from Big Sur and got into issues with git over ssh to our BitBucket server:

```
$ git clone ssh://git@xxx.xxx.xxx:7999/com/tangix-virtualtester.git
Cloning into 'tangix-virtualtester'...
git@bitbucket.tangix.com: Permission denied (publickey).
fatal: Could not read from remote repository.
```

Turns out `ssh` in macOS Ventura improved security by turning of RSA/SHA1 in the default configuration. Our internal bitbucket server is still requiring RSA/SH1 it seems.

Solution? Add the following to `~/.ssh/config` to re-enable RSA/SHA1:

```
Host xxx.xxx.xxx
  HostkeyAlgorithms +ssh-rsa
  PubkeyAcceptedAlgorithms +ssh-rsa
```

Seems like this change comes from OpenSSH upstream and was rolled into macOS Ventura. 