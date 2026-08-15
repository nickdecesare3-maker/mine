/**
 * Team Member Card block editor UI.
 *
 * @package TeamRosterForDivi
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import {
	useBlockProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	RichText,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	Button,
	RadioControl,
	Placeholder,
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
	const { mode, teamMemberId, manualPhotoUrl, manualName } = attributes;

	const { teamMembers, isLoading } = useSelect(
		( select ) => {
			const query = { per_page: -1, orderby: 'menu_order', order: 'asc', status: 'publish' };
			return {
				teamMembers: select( coreStore ).getEntityRecords( 'postType', 'trd_team_member', query ),
				isLoading: select( coreStore ).isResolving( 'getEntityRecords', [
					'postType',
					'trd_team_member',
					query,
				] ),
			};
		},
		[]
	);

	const memberOptions = [
		{ label: __( '— Select a team member —', 'team-roster-for-divi' ), value: 0 },
		...( teamMembers || [] ).map( ( member ) => ( {
			label: member.title?.rendered || __( '(untitled)', 'team-roster-for-divi' ),
			value: member.id,
		} ) ),
	];

	const hasContent = 'select' === mode ? !! teamMemberId : !! manualName;

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Team Member Source', 'team-roster-for-divi' ) } initialOpen>
					<RadioControl
						selected={ mode }
						options={ [
							{ label: __( 'Select an existing Team Member', 'team-roster-for-divi' ), value: 'select' },
							{ label: __( 'Enter details manually', 'team-roster-for-divi' ), value: 'manual' },
						] }
						onChange={ ( value ) => setAttributes( { mode: value } ) }
					/>
				</PanelBody>

				{ 'select' === mode && (
					<PanelBody title={ __( 'Team Member', 'team-roster-for-divi' ) } initialOpen>
						{ isLoading ? (
							<Spinner />
						) : (
							<SelectControl
								label={ __( 'Choose a Team Member', 'team-roster-for-divi' ) }
								value={ teamMemberId }
								options={ memberOptions }
								onChange={ ( value ) => setAttributes( { teamMemberId: Number( value ) } ) }
							/>
						) }
						<TextControl
							label={ __( 'Read Bio button label', 'team-roster-for-divi' ) }
							value={ attributes.buttonLabel }
							onChange={ ( value ) => setAttributes( { buttonLabel: value } ) }
						/>
					</PanelBody>
				) }

				{ 'manual' === mode && (
					<PanelBody title={ __( 'Photo', 'team-roster-for-divi' ) } initialOpen>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ ( media ) =>
									setAttributes( {
										manualPhotoId: media.id,
										manualPhotoUrl: media.sizes?.medium?.url || media.url,
									} )
								}
								allowedTypes={ [ 'image' ] }
								value={ attributes.manualPhotoId }
								render={ ( { open } ) => (
									<div className="trd-manual-photo-control">
										{ manualPhotoUrl && (
											<img src={ manualPhotoUrl } alt="" style={ { maxWidth: '100%', display: 'block', marginBottom: '8px' } } />
										) }
										<Button variant="secondary" onClick={ open }>
											{ manualPhotoUrl
												? __( 'Replace photo', 'team-roster-for-divi' )
												: __( 'Select photo', 'team-roster-for-divi' ) }
										</Button>
										{ manualPhotoUrl && (
											<Button
												variant="link"
												isDestructive
												onClick={ () => setAttributes( { manualPhotoId: 0, manualPhotoUrl: '' } ) }
											>
												{ __( 'Remove', 'team-roster-for-divi' ) }
											</Button>
										) }
									</div>
								) }
							/>
						</MediaUploadCheck>
					</PanelBody>
				) }

				{ 'manual' === mode && (
					<PanelBody title={ __( 'Details', 'team-roster-for-divi' ) } initialOpen>
						<TextControl
							label={ __( 'Name', 'team-roster-for-divi' ) }
							value={ attributes.manualName }
							onChange={ ( value ) => setAttributes( { manualName: value } ) }
						/>
						<TextControl
							label={ __( 'Job Title', 'team-roster-for-divi' ) }
							value={ attributes.manualJobTitle }
							onChange={ ( value ) => setAttributes( { manualJobTitle: value } ) }
						/>
						<TextControl
							label={ __( 'Company', 'team-roster-for-divi' ) }
							value={ attributes.manualCompany }
							onChange={ ( value ) => setAttributes( { manualCompany: value } ) }
						/>
						<TextControl
							type="email"
							label={ __( 'Email', 'team-roster-for-divi' ) }
							value={ attributes.manualEmail }
							onChange={ ( value ) => setAttributes( { manualEmail: value } ) }
						/>
						<TextControl
							type="tel"
							label={ __( 'Phone', 'team-roster-for-divi' ) }
							value={ attributes.manualPhone }
							onChange={ ( value ) => setAttributes( { manualPhone: value } ) }
						/>
						<TextControl
							type="url"
							label={ __( 'Link (LinkedIn, website, etc.)', 'team-roster-for-divi' ) }
							value={ attributes.manualLink }
							onChange={ ( value ) => setAttributes( { manualLink: value } ) }
						/>
						<TextControl
							label={ __( 'Read Bio button label', 'team-roster-for-divi' ) }
							value={ attributes.buttonLabel }
							onChange={ ( value ) => setAttributes( { buttonLabel: value } ) }
						/>
						<p>{ __( 'Biography', 'team-roster-for-divi' ) }</p>
						<RichText
							tagName="div"
							className="trd-manual-bio-editor"
							value={ attributes.manualBio }
							onChange={ ( value ) => setAttributes( { manualBio: value } ) }
							placeholder={ __( 'Write a short biography…', 'team-roster-for-divi' ) }
							multiline="p"
						/>
					</PanelBody>
				) }
			</InspectorControls>

			{ hasContent ? (
				<ServerSideRender block="team-roster-for-divi/team-member-card" attributes={ attributes } />
			) : (
				<Placeholder
					icon="id-alt"
					label={ __( 'Team Member Card', 'team-roster-for-divi' ) }
					instructions={ __(
						'Select an existing Team Member from the sidebar, or switch to manual entry and fill in the details.',
						'team-roster-for-divi'
					) }
				/>
			) }
		</div>
	);
}
