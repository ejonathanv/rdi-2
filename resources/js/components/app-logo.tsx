import rdiLogo from '../../img/rdi-logo.svg';

export default function AppLogo() {
    return (
        <img
            src={rdiLogo}
            alt="RDI"
            className="h-auto w-full object-contain object-left group-data-[collapsible=icon]:size-8 group-data-[collapsible=icon]:object-cover group-data-[collapsible=icon]:object-[12%_center]"
        />
    );
}
