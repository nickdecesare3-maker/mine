/**
 * Team Member Card block registration.
 *
 * @package TeamRosterForDivi
 */
import { registerBlockType } from '@wordpress/blocks';
import metadata from '../block.json';
import Edit from './edit';

registerBlockType( metadata.name, {
	edit: Edit,
	// Dynamic block: the front end (and editor preview) are always
	// server-rendered by Trd_Blocks::render_team_member_card() so the
	// stored markup for this block is just its attributes.
	save: () => null,
} );
