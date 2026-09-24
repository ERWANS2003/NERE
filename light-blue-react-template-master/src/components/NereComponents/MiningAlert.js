import React, { useState } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * MiningAlert - Néré Mining themed alert component
 * 
 * @component
 * @param {string} type - 'success', 'warning', 'danger', 'info'
 * @param {string} title - Alert title
 * @param {boolean} dismissible - Show close button
 * @param {function} onDismiss - Dismiss callback
 * @param {string} className - Additional CSS classes
 * @param {ReactNode} children - Alert content
 */
const MiningAlert = ({
  type = 'info',
  title,
  dismissible = false,
  onDismiss,
  className,
  icon,
  children,
  ...props
}) => {
  const [isVisible, setIsVisible] = useState(true);

  const handleDismiss = () => {
    setIsVisible(false);
    if (onDismiss) {
      onDismiss();
    }
  };

  if (!isVisible) {
    return null;
  }

  const alertClass = classNames(
    'alert-mining',
    {
      'alert-success': type === 'success',
      'alert-warning': type === 'warning',
      'alert-danger': type === 'danger',
      'alert-info': type === 'info',
    },
    className
  );

  const iconMap = {
    success: 'la la-check-circle',
    warning: 'la la-exclamation-triangle',
    danger: 'la la-times-circle',
    info: 'la la-info-circle',
  };

  const defaultIcon = iconMap[type];

  return (
    <div className={alertClass} role="alert" {...props}>
      <div className="d-flex">
        {(icon || defaultIcon) && (
          <i className={`${icon || defaultIcon} mr-2 flex-shrink-0`} />
        )}
        <div className="flex-grow-1">
          {title && <strong>{title}</strong>}
          {children}
        </div>
        {dismissible && (
          <button
            type="button"
            className="btn-close ml-2 flex-shrink-0"
            onClick={handleDismiss}
            aria-label="Close"
          >
            <i className="la la-times" />
          </button>
        )}
      </div>
    </div>
  );
};

MiningAlert.propTypes = {
  type: PropTypes.oneOf(['success', 'warning', 'danger', 'info']),
  title: PropTypes.string,
  dismissible: PropTypes.bool,
  onDismiss: PropTypes.func,
  icon: PropTypes.string,
  className: PropTypes.string,
  children: PropTypes.node.isRequired,
};

export default MiningAlert;
