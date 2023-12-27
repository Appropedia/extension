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

function appropedia.emailDomain( name )
	if not name then error( 'missing user name' ) end
	return php.emailDomain( name )
end

function appropedia.pageCategories( page )
	if not page then error( 'missing page' ) end
	return php.pageCategories( page )
end

return appropedia
