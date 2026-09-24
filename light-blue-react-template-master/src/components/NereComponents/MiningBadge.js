import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * MiningBadge - Néré Mining themed badge component
 * 
 * @component
 * @param {string} type - 'success', 'warning', 'danger', 'gold', 'info', 'default'
 * @param {string} size - 'sm', 'md', 'lg'
 * @param {string} className - Additional CSS classes
 * @param {ReactNode} children - Badge content
 */
const MiningBadge = ({
  type = 'default',
  size = 'md',
  className,
  icon,
  children,
  ...props
}) => {
  const badgeClass = classNames(
    'badge-mining',
    {
      'badge-mining-success': type === 'success',
      'badge-mining-warning': type === 'warning',
      'badge-mining-danger': type === 'danger',
      'badge-mining-gold': type === 'gold',
      'badge-mining-info': type === 'info',
      'badge-mining-default': type === 'default',
      'badge-sm': size === 'sm',
      'badge-md': size === 'md',
      'badge-lg': size === 'lg',
    },
    className
  );

  return (
    <span className={badgeClass} {...props}>
      {icon && <i className={`${icon} mr-1`} />}
      {children}
    </span>
  );
};

MiningBadge.propTypes = {
  type: PropTypes.oneOf(['success', 'warning', 'danger', 'gold', 'info', 'default']),
  size: PropTypes.oneOf(['sm', 'md', 'lg']),
  icon: PropTypes.string,
  className: PropTypes.string,
  children: PropTypes.node.isRequired,
};

export default MiningBadge;
