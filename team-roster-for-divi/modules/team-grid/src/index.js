/**
 * Team Grid block registration.
 *
 * @package TeamRosterForDivi
 */
import { registerBlockType } from '@wordpress/blocks';
import metadata from '../block.json';
import Edit from './edit';

registerBlockType( metadata.name, {
	edit: Edit,
	// Dynamic block: always server-rendered by
	// Trd_Team_Grid_Render::render() from the current attributes.
	save: () => null,
} );
