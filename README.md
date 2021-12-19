TimeRanks
=========

A PocketMine - MP plugin that lets you easily create configurable ranks for your server. Ranks are time-based, so TimeRanks is ideal to give players a rank based on their time spent online.

*Latest release: https://poggit.pmmp.io/p/TimeRanks/*

*Latest development phars: https://poggit.pmmp.io/ci/luca28pet/TimeRanks/TimeRanks*

**Ranks Configuration**

Ranks configuration is done inside _ranks.yml_.
TimeRanks requires a *default* rank, which is the one assigned to new players.
This is how you add a default rank:
```
- name: "DefaultRank"
  default: true
```
Note: we do not specify the 'minutes' parameter.
It has not to be called DefaultRank, you can give it any name you want.

Other ranks have to be written in the _ranks.yml_ file, under the default rank, like this:
```
- name: "ExampleRank"
  minutes: 20
  message: "You are now rank ExampleRank"
  commands:
  - "tell \"{%player}\" You ranked up!"
```
We must specify a 'minutes' parameter (after how many minutes spent online a player will get that rank).
Optionally you can add a 'message', which is sent to the player, and 'commands' that get executed when the player ranks up to that rank.

An example of a complete _ranks.yml_ is:
```
ranks:
  - name: "Beginner"
    default: true

  - name: "Intermediate"
    minutes: 120
    message: "You have ranked up to Intermediate"
    commands:
    - "setgroup \"{%player}\" Intermediate"

  - name: "Veteran"
    minutes: 600
    message: "You have ranked up to Veteran"
    commands:
    - "setgroup \"{%player}\" Veteran"
```
As shown above, the 'commands' parameter can be used to integrate TimeRanks with your permissions manager plugin of choice (e.g. PurePerms).

**General Configuration**

In the _config.yml_ file you can manage other settings like how data is saved.
The default option for data storage is SQLite, which does not require additional configuration on your part. Currently, only SQLite and MySQL are supported.
```
---
database:
  # The database type. "sqlite" and "mysql" are supported.
  type: sqlite

  # Edit these settings only if you choose "sqlite".
  sqlite:
    # The file name of the database in the plugin data folder.
    # You can also put an absolute path here.
    file: data.sqlite
  # Edit these settings only if you choose "mysql".
  mysql:
    host: 127.0.0.1
    # Avoid using the "root" user for security reasons.
    username: root
    password: ""
    schema: your_schema
  # The maximum number of simultaneous SQL queries
  # Recommended: 1 for sqlite, 2 for MySQL. You may want to further increase this value if your MySQL connection is very slow.
  worker-limit: 1
...

```

**Translations**

You can translate all the messages, /tr command description and usage from the _*lang.yml*_ file

**Permissions**

```
permissions:
  timeranks.command.rank.self:
    description: "Allows to execute /rank command and check play time"
    default: "true"
  timeranks.command.rank.others:
    description: "Allows to show the minutes another player has played"
    default: "op"
  timeranks.command.timeranks:
    description: "Allows to execute the /timeranks command"
    default: "true"
  timeranks.command.timeranksadmin:
    description: "Allows to execute the /tradm command"
    default: "op"
  timeranks.exempt:
    description: "Exempt a player from rankup"
    default: "op"
```

