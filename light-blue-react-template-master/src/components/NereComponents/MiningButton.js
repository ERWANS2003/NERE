import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import s from './NereComponents.module.scss';

/**
 * MiningButton - Néré Mining themed button component
 * 
 * @component
 * @param {string} variant - 'primary', 'secondary', 'outline', 'gold', 'crimson'
 * @param {string} size - 'sm', 'md', 'lg', 'xl'
 * @param {function} onClick - Click handler
 * @param {boolean} disabled - Disabled state
 * @param {boolean} loading - Loading state
 * @param {string} className - Additional CSS classes
 * @param {ReactNode} children - Button content
 */
const MiningButton = ({
  variant = 'primary',
  size = 'md',
  onClick,
  disabled = false,
  loading = false,
  className,
  children,
  ...props
}) => {
  const buttonClass = classNames(
    'btn',
    {
      'btn-mining-primary': variant === 'primary' || variant === 'gold',
      'btn-mining-secondary': variant === 'secondary' || variant === 'crimson',
      'btn-mining-outline': variant === 'outline',
      'btn-sm': size === 'sm',
      'btn-md': size === 'md',
      'btn-lg': size === 'lg',
      'btn-xl': size === 'xl',
      'disabled': disabled || loading,
    },
    className
  );

  return (
    <button
      className={buttonClass}
      onClick={onClick}
      disabled={disabled || loading}
      {...props}
    >
      {loading && <i className="la la-spinner spin-mining mr-2" />}
      {children}
    </button>
  );
};

MiningButton.propTypes = {
  variant: PropTypes.oneOf(['primary', 'secondary', 'outline', 'gold', 'crimson']),
  size: PropTypes.oneOf(['sm', 'md', 'lg', 'xl']),
  onClick: PropTypes.func,
  disabled: PropTypes.bool,
  loading: PropTypes.bool,
  className: PropTypes.string,
  children: PropTypes.node.isRequired,
};

export default MiningButton;
