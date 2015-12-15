# SpecialBlock

SpecialBlock includes four block types:
  * Healing Block
  * Effect Block
  * Damage Block
  * Command Block
  
You can bind type to a block through config or command:
```
# Removed command due to: useless
```
Note: Effect Block, Healing Block and Damage Block uses block ID, but Command Block uses positions just add your block position in blocks.yml in this format:
```
x:y:z:world:
- 'cmd1'
- 'cmd2'
- and so on...
```
All commands will be executed as player unless you add these variables:
```
- "{OP}" Execute command with OP permission
- "{CON}" Execute command as console
- "{AO}Steve" Execute command as other player (Player name should be added after variable)
- "{AOOP}" Same as above only with OP permission
```
# Defaults:
```
Heal block: 121 (END_STONE)
Damage block: 49 (OBSIDIAN)
Effect block: 246 (GLOWING_OBSIDIAN)
```
