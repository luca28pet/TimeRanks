TimeRanks
=========

A PocketMine - MP plugin that lets you easily create configurable ranks for your server. Ranks are time-based, so TimeRanks is ideal to give players a rank based on their time spent online.
Each rank is linked to a PurePerms group, so you can give players different permissions depending on how much time they spent online.

**Configuration**

TimeRanks requires a *default* rank, which is the one assigned to new players.
In the *ranks.yml* config its format is:
```
DefaultRank:
  default: true
  pureperms_group: Default
```
Note: we do not specify the 'minutes' parameter. The parameter 'pureperms_group' is the PP group we want to link to the default rank (i.e. the PP group containing the permissions you want to give to new players).
It has not to be called DefaultRank, you can give it any name you want.

Other ranks have to be written in the *ranks.yml* like this:
```
ExampleRank:
  minutes: 20
  pureperms_group: Example
  message: "You are now rank ExampleRank"
  commands:
  - "tell {player} You ranked up!"
```
We specify a 'minutes' parameter (after how many minutes spent online a player will get that rank). The parameter 'pureperms_group' is the PP group we want to link to that rank.

**Config.yml**

In the *config.yml* you can change the data provider or translate the messages. 
Note: you can use the sqlite3 provider only if the sqlite3 extension is included and enabled in your php binaries
```
#choose the data provider where all data will be stored. Available: json, yaml, sqlite3 (ONLY WITH APPROPRIATE PHP BINARIES)
data-provider: json
#you can translate these messages.
#{name}: Player's name.
#{minutes}: The number of minutes the player has played on the server.
#{rank}: The corresponding rank to the minutes played.
#{line}: Adds a new line to the message.
message-player-minutes-played: "§c{name} §ehas played §c{minutes} §eminutes on this server. {line}Rank: §c{rank}"
message-player-never-played: "§c{name} §ehas never played on this server."
message-minutes-played: "§eYou have played §c{minutes} §eminutes on this server. {line}Rank: §c{rank}"
message-usage: "§eUsage: /tr check [name]"
```
