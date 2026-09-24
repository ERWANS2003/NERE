import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import s from './NereComponents.module.scss';

/**
 * MiningCard - Néré Mining themed card component
 * 
 * @component
 * @param {string} accent - 'gold', 'crimson', 'blue' - left border color
 * @param {boolean} hover - Enable hover effects
 * @param {string} className - Additional CSS classes
 * @param {ReactNode} children - Card content
 */
const MiningCard = ({
  accent = 'gold',
  hover = true,
  className,
  children,
  title,
  subtitle,
  ...props
}) => {
  const cardClass = classNames(
    'card-mining',
    {
      'card-mining-accent': accent,
      [`accent-${accent}`]: accent,
      'hover-lift': hover,
    },
    className
  );

  return (
    <div className={cardClass} {...props}>
      {title && (
        <div className="card-mining-title">
          {title}
        </div>
      )}
      {subtitle && (
        <div className="card-mining-subtitle">
          {subtitle}
        </div>
      )}
      <div>
        {children}
      </div>
    </div>
  );
};

MiningCard.propTypes = {
  accent: PropTypes.oneOf(['gold', 'crimson', 'blue']),
  hover: PropTypes.bool,
  className: PropTypes.string,
  title: PropTypes.string,
  subtitle: PropTypes.string,
  children: PropTypes.node.isRequired,
};

export default MiningCard;
