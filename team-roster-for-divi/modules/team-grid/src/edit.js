/**
 * Team Grid block editor UI.
 *
 * @package TeamRosterForDivi
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useBlockProps, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import {
	PanelBody,
	RadioControl,
	CheckboxControl,
	ToggleControl,
	SelectControl,
	RangeControl,
	TextControl,
	Spinner,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 */
export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { displayMode, groupIds, excludeIds, groupSections } = attributes;

	const { groups, isLoadingGroups } = useSelect( ( select ) => {
		const query = { per_page: -1, hide_empty: false, orderby: 'name', order: 'asc' };
		return {
			groups: select( coreStore ).getEntityRecords( 'taxonomy', 'trd_team_group', query ),
			isLoadingGroups: select( coreStore ).isResolving( 'getEntityRecords', [ 'taxonomy', 'trd_team_group', query ] ),
		};
	}, [] );

	const { members, isLoadingMembers } = useSelect( ( select ) => {
		const query = { per_page: -1, orderby: 'menu_order', order: 'asc', status: 'publish' };
		return {
			members: select( coreStore ).getEntityRecords( 'postType', 'trd_team_member', query ),
			isLoadingMembers: select( coreStore ).isResolving( 'getEntityRecords', [
				'postType',
				'trd_team_member',
				query,
			] ),
		};
	}, [] );

	const toggleInArray = ( array, value, checked ) =>
		checked ? [ ...array, value ] : array.filter( ( item ) => item !== value );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Who to show', 'team-roster-for-divi' ) } initialOpen>
					<RadioControl
						selected={ displayMode }
						options={ [
							{ label: __( 'All team members', 'team-roster-for-divi' ), value: 'all' },
							{ label: __( 'Only selected groups', 'team-roster-for-divi' ), value: 'groups' },
						] }
						onChange={ ( value ) => setAttributes( { displayMode: value } ) }
					/>

					{ 'groups' === displayMode && (
						<>
							<p>{ __( 'Groups to include:', 'team-roster-for-divi' ) }</p>
							{ isLoadingGroups ? (
								<Spinner />
							) : (
								<div className="trd-group-checkbox-list">
									{ ( groups || [] ).map( ( group ) => (
										<CheckboxControl
											key={ group.id }
											label={ group.name }
											checked={ groupIds.includes( group.id ) }
											onChange={ ( checked ) =>
												setAttributes( { groupIds: toggleInArray( groupIds, group.id, checked ) } )
											}
										/>
									) ) }
									{ ! isLoadingGroups && ! ( groups || [] ).length && (
										<p className="description">{ __( 'No groups created yet.', 'team-roster-for-divi' ) }</p>
									) }
								</div>
							) }
						</>
					) }

					<ToggleControl
						label={ __( 'Render each group as its own labeled section', 'team-roster-for-divi' ) }
						help={ __(
							'Off shows one flat grid. On splits the grid into a heading + sub-grid per group.',
							'team-roster-for-divi'
						) }
						checked={ groupSections }
						onChange={ ( value ) => setAttributes( { groupSections: value } ) }
					/>

					<SelectControl
						label={ __( 'Sort order', 'team-roster-for-divi' ) }
						value={ attributes.sortOrder }
						options={ [
							{ label: __( 'Manual (drag-and-drop order)', 'team-roster-for-divi' ), value: 'menu_order' },
							{ label: __( 'Name (A–Z)', 'team-roster-for-divi' ), value: 'title_asc' },
							{ label: __( 'Name (Z–A)', 'team-roster-for-divi' ), value: 'title_desc' },
						] }
						onChange={ ( value ) => setAttributes( { sortOrder: value } ) }
					/>

					<p>{ __( 'Exclude specific team members:', 'team-roster-for-divi' ) }</p>
					{ isLoadingMembers ? (
						<Spinner />
					) : (
						<div className="trd-group-checkbox-list">
							{ ( members || [] ).map( ( member ) => (
								<CheckboxControl
									key={ member.id }
									label={ member.title?.rendered || __( '(untitled)', 'team-roster-for-divi' ) }
									checked={ excludeIds.includes( member.id ) }
									onChange={ ( checked ) =>
										setAttributes( { excludeIds: toggleInArray( excludeIds, member.id, checked ) } )
									}
								/>
							) ) }
						</div>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Layout', 'team-roster-for-divi' ) } initialOpen={ false }>
					<RangeControl
						label={ __( 'Columns (desktop)', 'team-roster-for-divi' ) }
						value={ attributes.columns }
						onChange={ ( value ) => setAttributes( { columns: value } ) }
						min={ 1 }
						max={ 6 }
					/>
					<RangeControl
						label={ __( 'Columns (tablet)', 'team-roster-for-divi' ) }
						value={ attributes.columnsTablet }
						onChange={ ( value ) => setAttributes( { columnsTablet: value } ) }
						min={ 1 }
						max={ 6 }
					/>
					<RangeControl
						label={ __( 'Columns (mobile)', 'team-roster-for-divi' ) }
						value={ attributes.columnsMobile }
						onChange={ ( value ) => setAttributes( { columnsMobile: value } ) }
						min={ 1 }
						max={ 6 }
					/>
					<TextControl
						label={ __( 'Read Bio button label', 'team-roster-for-divi' ) }
						value={ attributes.buttonLabel }
						onChange={ ( value ) => setAttributes( { buttonLabel: value } ) }
					/>
				</PanelBody>

				<PanelColorSettings
					title={ __( 'Card style overrides', 'team-roster-for-divi' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: attributes.cardBackground,
							onChange: ( value ) => setAttributes( { cardBackground: value || '' } ),
							label: __( 'Card background', 'team-roster-for-divi' ),
						},
						{
							value: attributes.cardTextColor,
							onChange: ( value ) => setAttributes( { cardTextColor: value || '' } ),
							label: __( 'Card text color', 'team-roster-for-divi' ),
						},
					] }
				>
					<RangeControl
						label={ __( 'Card corner radius (px)', 'team-roster-for-divi' ) }
						value={ attributes.cardRadius }
						onChange={ ( value ) => setAttributes( { cardRadius: value } ) }
						min={ 0 }
						max={ 40 }
					/>
				</PanelColorSettings>
			</InspectorControls>

			<ServerSideRender block="team-roster-for-divi/team-grid" attributes={ attributes } />
		</div>
	);
}
