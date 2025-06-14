import os
import glob
import runpy

script_dir = 'python_scripts'

def run_all():
    py_files = glob.glob(os.path.join(script_dir, '*.py'))
    for script in py_files:
        print(f'Running {script}...')
        runpy.run_path(script)

if __name__ == '__main__':
    run_all()
