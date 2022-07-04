# Tenth ring of hell - Troubleshooting Java

If Java had been invented in the 14th century, I am sure Dante Alighieri would have included a tenth ring of hell where Dante would have to troubleshoot a failed Java installation. Today I had to endure just that.

My Sencha ExtJS front-end compilation started failing with various cryptic error messages. This compilation has been working but a recent upgrade to Monterey and prompted upgrades to Java later it now started failing. Some examples of error-messages:

```
Error: Could not create the Java Virtual Machine.
Error: A fatal exception has occurred. Program will exit.
```

and after a re-installation of Java 8:

```
[NSPlaceholderString initWithFormat:locale:arguments:]: nil argument
```

and after a re-installation of JDK 7:

```
Unable to create javax script engine for javascript
```

Reinstallation of OpenJDK, Oracle Java and Tenmurin Java I was close to tears. `java_home` now shows an impressive amount of installed Java run-times, which one to pick?

```
msa@verdandi ci4-zen-zone % /usr/libexec/java_home -V
Matching Java Virtual Machines (8):
    18.0.1 (x86_64) "Eclipse Adoptium" - "OpenJDK 18.0.1" /Library/Java/JavaVirtualMachines/temurin-18.jdk/Contents/Home
    17.0.3.1 (x86_64) "Oracle Corporation" - "Java SE 17.0.3.1" /Library/Java/JavaVirtualMachines/jdk-17.0.3.1.jdk/Contents/Home
    17.0.3 (x86_64) "Homebrew" - "OpenJDK 17.0.3" /usr/local/Cellar/openjdk@17/17.0.3/libexec/openjdk.jdk/Contents/Home
    16.0.1 (x86_64) "AdoptOpenJDK" - "AdoptOpenJDK 16" /Library/Java/JavaVirtualMachines/adoptopenjdk-16.jdk/Contents/Home
    1.8.333.02 (x86_64) "Oracle Corporation" - "Java" /Library/Internet Plug-Ins/JavaAppletPlugin.plugin/Contents/Home
    1.8.0_242 (x86_64) "AdoptOpenJDK" - "OpenJDK 8" /Users/msa/bin/Sencha/Cmd/7.5.1.20/.install4j/jre.bundle/Contents/Home
    1.8.0_231 (x86_64) "Oracle Corporation" - "Java SE 8" /Library/Java/JavaVirtualMachines/jdk1.8.0_231.jdk/Contents/Home
    1.7.0_80 (x86_64) "Oracle Corporation" - "Java SE 7" /Library/Java/JavaVirtualMachines/jdk1.7.0_80.jdk/Contents/Home
/Library/Java/JavaVirtualMachines/temurin-18.jdk/Contents/Home
```

Turns out the one I need is `1.8.0_242` because it contains the javascript execution engine needed for `ant`. However, this version is not compatible with macOS Monterey generating the `NSPlaceholderString` error. By an obscure reference in an `ant` bug report I cam across the config `"bypass_lp": true` to add to `ant`.

**Having applications rely on an execution framework that can be independently updated or even destroyed by the OS is so stupid I can not even put words on it.**

One day completely down the drain troubleshooting stupid Java! May all Java developers burn in hell!