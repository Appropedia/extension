-- The Appropedia Lua library gets data for the Appropedia Lua module
-- See https://www.appropedia.org/Module:Appropedia
local appropedia = {}
local php

function appropedia.setupInterface( options )
	-- Remove setup function
	appropedia.setupInterface = nil

	-- Copy the PHP callbacks to a local variable, and remove the global
	php = mw_interface
	mw_interface = nil

	-- Install into the mw global
	mw = mw or {}
	mw.ext = mw.ext or {}
	mw.ext.appropedia = appropedia

	-- Indicate that we're loaded
	package.loaded['mw.ext.appropedia'] = appropedia
end

function appropedia.emailDomain( user )
	return php.emailDomain( user )
end

function appropedia.pageExists( page )
	return php.pageExists( page )
end

function appropedia.pageCategories( page )
	return php.pageCategories( page )
end

function appropedia.fileUses( file )
	return php.fileUses( file )
end

return appropedia
