Final Fantasy XIV: A Realm Reborn Lodestone PHP API
==============================================

An updated version of the original project, to work with the "new" Lodestone.

As of typing, I've not updated anything (I'm more interested in playing right now!) but I'll be working on this in the near future.

* * *

Available Features
==============================================
**Character Searching**
>$obj->SearchCharacterList ( $CharacterName, $Server = false, $Class = false )

**Character Details Page**
>$obj->GetCharacterData ( $CharacterID )

**Character Biography**
>$obj->GetCharacterBiography( $CharacterID )

**Character Recent Blog Entries**
>$obj->GetCharacterRecentBlogEntries( $CharacterID )

**Character Following Count**
>$obj->GetCharacterFollowingCount( $CharacterID )

**Character Follower Count**
>$obj->GetCharacterFollowerCount( $CharacterID )

**Character History**
>$obj->GetCharacterHistory ( $CharacterID, $page = 1 )    
*defaults to 100 per page* 

* * *


Copyright Details
==============================================
Original idea / version: http://github.com/rysas/Final-Fantasy-XIV-Lodestone-PHP-API
Updated by Loki: http://lokizilla.net | http://github.com/lokizilla

© 2010 SQUARE ENIX CO., LTD. All Rights Reserved. FINAL FANTASY, FFXIV, SQUARE ENIX and the SQUARE ENIX logo are registered trademarks or trademarks of Square Enix Holdings Co., Ltd. "PlayStation" and the "PS" Family logo are registered trademarks and "PS3" is a trademark of Sony Computer Entertainment Inc. The PlayStation Network Logo is a service mark of Sony Computer Entertainment Inc. The ESRB rating icon is a registered trademark of the Entertainment Software Association. All other trademarks are the properties of their respective owners.
