import React from 'react';

export const Button = ( props ) => <button { ...props } />;
export const Card = ( { children, ...props } ) => (
	<div { ...props }>{ children }</div>
);
export const CardBody = ( { children } ) => <div>{ children }</div>;
export const CardHeader = ( { children } ) => <div>{ children }</div>;
export const Notice = ( { children, status } ) => (
	<div data-status={ status }>{ children }</div>
);
export const SelectControl = ( props ) => <select { ...props } />;
export const Spinner = () => <div className="spinner" />;
export const __experimentalText = ( { children } ) => (
	<span>{ children }</span>
);
