import React from 'react';
import { Modal } from 'react-bootstrap';
import { BeatLoading } from 'respinner'

export default class Spinner extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            show: false,
        }
    }

    componentWillUpdate(props) {
        this.state.show = props.show;
    }

    render() {
        return (
                <Modal show={this.state.show}>
                    <Modal.Body>
                        <p className="center-text">
                            <BeatLoading fill="#4197ff" count={6} />
                        </p>
                    </Modal.Body>
                </Modal>
        );
    }
}
